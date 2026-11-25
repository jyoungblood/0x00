<?php

namespace Deployer;

use RuntimeException;

set('writable_mode', 'chmod');
set('writable_chmod_mode', '0750');
set('writable_use_sudo', false);
set('writable_recursive', false);
set('use_atomic_symlink', true);
// Retain releases until process-drain and disk-retention policy are proven on this host.
set('keep_releases', -1);
set('default_timeout', 600);
set('composer_options', '--no-dev --prefer-dist --no-interaction --optimize-autoloader');

task('release:preflight', function (): void {
    $directory = getenv('DEPLOY_ARTIFACT_DIR');
    $revision = (string) input()->getOption('revision');
    if (! is_string($directory) || ! preg_match('/^[a-f0-9]{40}$/', $revision)
        || ! is_file($directory.'/source.tar') || ! is_file($directory.'/build/manifest.json') || ! is_file($directory.'/REVISION')
        || trim((string) file_get_contents($directory.'/REVISION')) !== $revision) {
        throw new RuntimeException('Build a matching artifact with ./deploy.sh first.');
    }
    if (! test('[ -s {{deploy_path}}/shared/.env ]')) {
        throw new RuntimeException('Configure shared/.env before preparing a release.');
    }
    run('{{bin/composer}} --version');
});

// Atomic creation, with ownership by invocation: a failed competing deploy cannot unlock ours.
task('deploy:lock', function (): void {
    $token = getenv('DEPLOY_LOCK_TOKEN');
    if (! is_string($token) || ! preg_match('/^[a-f0-9]{32}$/', $token)) {
        throw new RuntimeException('Use ./deploy.sh to acquire a deployment lock.');
    }
    run('(set -C; printf %s '.quote($token).' > {{deploy_path}}/.dep/deploy.lock)');
});

task('deploy:unlock', function (): void {
    $token = getenv('DEPLOY_LOCK_TOKEN');
    if (is_string($token) && preg_match('/^[a-f0-9]{32}$/', $token)) {
        run('if [ "$(cat {{deploy_path}}/.dep/deploy.lock 2>/dev/null)" = '.quote($token).' ]; then rm {{deploy_path}}/.dep/deploy.lock; fi');
    }
});

task('deploy:update_code', function (): void {
    $directory = (string) getenv('DEPLOY_ARTIFACT_DIR');
    upload($directory.'/source.tar', '{{release_path}}/source.tar');
    run('tar -xf {{release_path}}/source.tar -C {{release_path}} && rm {{release_path}}/source.tar');
    upload($directory.'/REVISION', '{{release_path}}/REVISION');
});

task('release:assets', function (): void {
    $directory = (string) getenv('DEPLOY_ARTIFACT_DIR');
    // Each release owns its manifest; hashed files remain accessible to older browser tabs.
    run('mkdir -p {{deploy_path}}/shared/build-assets {{release_path}}/public/build');
    upload($directory.'/build/', '{{release_path}}/public/build/');
    run('cp -a {{release_path}}/public/build/assets/. {{deploy_path}}/shared/build-assets/');
    run('rm -rf {{release_path}}/public/build/assets && ln -s {{deploy_path}}/shared/build-assets {{release_path}}/public/build/assets');
    run('cp {{release_path}}/REVISION {{release_path}}/public/deploy-revision.txt');
    run('cp {{release_path}}/deploy/revision.php {{release_path}}/public/deploy-revision.php');
});

task('release:cache', function (): void {
    run('mkdir -p {{release_path}}/bootstrap/cache/views');
    // view:cache must not clear the compiled views of the release still serving traffic.
    run('cd {{release_path}} && VIEW_COMPILED_PATH={{release_path}}/bootstrap/cache/views {{bin/php}} artisan optimize --no-interaction');
});

task('release:check', function (): void {
    run('cd {{release_path}} && {{bin/php}} deploy/check-release.php '.quote(get('expected_database')));
});

task('release:migrate', function (): void {
    $credentials = require __DIR__.'/read-credentials.php';
    $payload = base64_encode(json_encode($credentials, JSON_THROW_ON_ERROR));
    // SSH receives the command on stdin; the quoted heredoc avoids shell expansion and argv secrets.
    run('cd {{release_path}} && {{bin/php}} deploy/migrate.php '.quote(get('expected_database'))." <<'DEPLOY_DATABASE_CREDENTIALS'\n%migration_credentials%\nDEPLOY_DATABASE_CREDENTIALS",
        secrets: ['migration_credentials' => $payload]);
});

task('release:mark_prepared', function (): void {
    run('cp {{release_path}}/REVISION {{release_path}}/.prepared');
});

task('release:require_prepared', function (): void {
    $revision = (string) input()->getOption('revision');
    if (! preg_match('/^[a-f0-9]{40}$/', $revision)
        || trim(run('cat {{release_path}}/REVISION')) !== $revision) {
        throw new RuntimeException('Prepared release must match the commit checked locally by ./deploy.sh --activate.');
    }
    run('test -s {{release_path}}/.prepared && cmp {{release_path}}/REVISION {{release_path}}/.prepared');
    run('test -s {{release_path}}/deploy/switch-webroot.sh && test -s {{release_path}}/public/deploy-revision.php && test -L {{release_path}}/public/.user.ini && test -L {{release_path}}/public/.well-known');
});

task('release:webroot', function (): void {
    run('bash {{release_path}}/deploy/switch-webroot.sh '.quote(get('public_root')).' '.quote(get('current_path').'/public'));
});

task('release:workers', function (): void {
    if (get('queue_worker', false)) {
        run('cd {{current_path}} && {{bin/php}} artisan queue:restart --no-interaction');
    }
});

task('release:health', function (): void {
    $url = rtrim(get('public_url'), '/');
    $revision = trim(run('cat {{current_path}}/REVISION'));
    foreach (['txt', 'php'] as $extension) {
        $served = run('curl --fail --silent --show-error --max-time 20 '.quote($url.'/deploy-revision.'.$extension.'?release='.$revision));
        if (trim($served) !== $revision) {
            throw new RuntimeException('Public traffic or PHP-FPM is not serving the published release. Check domain routing and PHP caches.');
        }
    }
    run('curl --fail --silent --show-error --max-time 20 --output /dev/null '.quote($url.'/up?release='.$revision));
    $manifest = json_decode(run('cat {{current_path}}/public/build/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($manifest as $entry) {
        if ($entry['isEntry'] ?? false) {
            foreach (array_merge([$entry['file']], $entry['css'] ?? []) as $asset) {
                run('curl --fail --silent --show-error --max-time 20 --output /dev/null '.quote($url.'/build/'.$asset));
            }
        }
    }
});

task('release:build', [
    'release:preflight', 'deploy:setup', 'deploy:lock', 'deploy:release',
    'deploy:update_code', 'deploy:shared', 'deploy:writable', 'deploy:vendors',
    'release:assets', 'release:cache', 'release:check', 'release:mark_prepared',
]);

desc('Prepare and check a release without migrations or changing current');
task('release:prepare', ['release:build', 'deploy:unlock']);

desc('Publish the prepared release and switch the initial web root');
task('release:activate', [
    'deploy:lock', 'release:require_prepared', 'release:check', 'release:migrate', 'deploy:symlink',
    'release:webroot', 'release:health', 'release:workers', 'deploy:unlock', 'deploy:success',
]);

task('deploy', [
    'release:build', 'release:migrate', 'deploy:symlink', 'release:health',
    'release:workers', 'deploy:unlock', 'deploy:success',
]);

foreach (['deploy', 'release:prepare', 'release:activate'] as $task) {
    fail($task, 'deploy:unlock');
}
