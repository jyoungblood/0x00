"""Offline deployment gate regression tests; no real Git push, SSH or install.

Usage: python3 test_predeploy.py [path/to/deploy.sh] [branch]
"""

import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest


WRAPPER = Path(sys.argv[1]).resolve() if len(sys.argv) > 1 else Path(__file__).with_name("deploy.sh")
BRANCH = sys.argv[2] if len(sys.argv) > 2 else "main"
REVISION = "a" * 40

FAKE_TOOL = r'''
import json, os, sys
from pathlib import Path

name = Path(sys.argv[0]).name
args = sys.argv[1:]
with open("commands.jsonl", "a") as log:
    log.write(json.dumps([name, *args]) + "\n")
if name == "composer" and args == ["predeploy"]:
    status = int(os.environ.get("GATE_EXIT", "0"))
    if status == 0:
        Path("public/build/assets").mkdir(parents=True)
        Path("public/build/manifest.json").write_text("{}")
        Path("gate-complete").touch()
    sys.exit(status)
if name == "git":
    if args == ["branch", "--show-current"]:
        print(os.environ["TEST_BRANCH"])
    elif args == ["status", "--porcelain"]:
        if os.environ.get("DIRTY") == "1" or (
            os.environ.get("CHANGE_DURING_CHECK") == "1" and Path("gate-complete").exists()
        ):
            print(" M app/Example.php")
    elif args == ["rev-parse", "HEAD"]:
        print(os.environ["TEST_REVISION"])
    elif args[:1] == ["push"]:
        pass
    elif args[:1] == ["archive"]:
        output = next(a.removeprefix("--output=") for a in args if a.startswith("--output="))
        Path(output).write_bytes(b"test artifact")
    else:
        sys.exit("Unexpected git command: " + repr(args))
elif name == "php":
    if args[:1] == ["-r"]:
        print("b" * 32)
    elif args[:1] not in (["deploy/read-credentials.php"], ["vendor/bin/dep"]):
        sys.exit("Unexpected PHP command: " + repr(args))
else:
    sys.exit("Unexpected command: " + name)
'''

PREPARED_HARNESS = r'''<?php
namespace Deployer;

$tasks = [];
$calls = [];
function set(...$args) {}
function desc(...$args) {}
function fail(...$args) {}
function task($name, $body) { $GLOBALS['tasks'][$name] = $body; }
function input() {
    return new class {
        function getOption($name) { return $GLOBALS['argv'][2]; }
    };
}
function run($command) {
    $GLOBALS['calls'][] = $command;
    return str_starts_with($command, 'cat ') ? $GLOBALS['argv'][3] : '';
}
require $argv[1];
try {
    $tasks['release:require_prepared']();
    echo json_encode(['passed' => true, 'calls' => $calls]);
} catch (\RuntimeException $e) {
    echo json_encode(['passed' => false, 'calls' => $calls, 'message' => $e->getMessage()]);
}
'''


class DeploymentGateTests(unittest.TestCase):
    def run_wrapper(self, mode=(), **settings):
        with tempfile.TemporaryDirectory(prefix="deploy-gate-") as directory:
            root = Path(directory)
            shutil.copyfile(WRAPPER, root / "deploy.sh")
            (root / "vendor/bin").mkdir(parents=True)
            (root / "vendor/bin/dep").touch()
            (root / "bin").mkdir()
            for name in ("git", "php", "composer"):
                executable = root / "bin" / name
                executable.write_text("#!" + sys.executable + "\n" + FAKE_TOOL)
                executable.chmod(0o755)
            env = {
                "PATH": str(root / "bin") + ":/usr/bin:/bin",
                "TMPDIR": str(root),
                "TEST_BRANCH": BRANCH,
                "TEST_REVISION": REVISION,
                **settings,
            }
            result = subprocess.run(
                ["/bin/bash", "deploy.sh", *mode], cwd=root, env=env,
                stdin=subprocess.DEVNULL, capture_output=True, text=True, timeout=15,
            )
            log = root / "commands.jsonl"
            commands = [json.loads(line) for line in log.read_text().splitlines()] if log.exists() else []
            return result, commands

    def test_failed_check_or_audit_service_stops_every_release_mode(self):
        for mode in ((), ("--prepare",), ("--activate",)):
            for status in ("1", "2"):
                with self.subTest(mode=mode, status=status):
                    result, commands = self.run_wrapper(mode, GATE_EXIT=status)
                    self.assertEqual(result.returncode, int(status), result.stderr)
                    self.assertIn(["composer", "predeploy"], commands)
                    self.assertFalse(any(c[0] == "php" or c[:2] == ["git", "push"] for c in commands))

    def test_passing_checks_precede_push_and_deployment(self):
        for mode, task in (((), "deploy"), (("--prepare",), "release:prepare"), (("--activate",), "release:activate")):
            with self.subTest(mode=mode):
                result, commands = self.run_wrapper(mode)
                self.assertEqual(result.returncode, 0, result.stderr)
                gate = commands.index(["composer", "predeploy"])
                deploy = ["php", "vendor/bin/dep", task, "production", "--revision=" + REVISION]
                self.assertGreater(commands.index(deploy), gate)
                pushes = [i for i, c in enumerate(commands) if c[:2] == ["git", "push"]]
                self.assertEqual(len(pushes), 0 if mode == ("--activate",) else 1)
                self.assertTrue(all(i > gate for i in pushes))

    def test_source_changed_during_checks_cannot_be_deployed(self):
        result, commands = self.run_wrapper(CHANGE_DURING_CHECK="1")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Tracked source changed", result.stderr)
        self.assertFalse(any(c[0] == "php" or c[:2] == ["git", "push"] for c in commands))

    def test_dirty_prepared_activation_stops_before_checks(self):
        result, commands = self.run_wrapper(("--activate",), DIRTY="1")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("clean checkout", result.stderr)
        self.assertNotIn(["composer", "predeploy"], commands)

    def test_prepared_release_must_match_checked_revision(self):
        php = shutil.which("php")
        self.assertIsNotNone(php, "PHP is required to test the prepared-release guard")
        recipe = WRAPPER.parent / "deploy/laravel.php"
        with tempfile.TemporaryDirectory(prefix="prepared-gate-") as directory:
            harness = Path(directory) / "prepared.php"
            harness.write_text(PREPARED_HARNESS)
            for expected, prepared, passes in ((REVISION, REVISION, True), (REVISION, "c" * 40, False), ("", REVISION, False)):
                with self.subTest(expected=expected, prepared=prepared):
                    result = subprocess.run(
                        [php, str(harness), str(recipe), expected, prepared],
                        capture_output=True, text=True, timeout=15,
                    )
                    self.assertEqual(result.returncode, 0, result.stderr)
                    report = json.loads(result.stdout)
                    self.assertEqual(report["passed"], passes)
                    if not passes:
                        self.assertFalse(any(".prepared" in c for c in report["calls"]))


if __name__ == "__main__":
    unittest.main(argv=[sys.argv[0]], verbosity=2)
