# Laravel Setup Guidelines

## System / global environment setup

Install these once on each development machine:

- **[Flock](https://flockphp.com/) (preferred)** — local PHP runtime and web server, with `.test` domains and HTTPS. [Laravel Herd](https://herd.laravel.com/) also works; use one or the other.
- **[DBngin](https://dbngin.com/)** — manage local database servers and versions. Use it for the database services your projects need; avoid running a second instance on the same port through Flock or Herd.
- **[Pun](https://pun.laravel.cloud/)** — PHP formatting, linting and static analysis. A global CLI is convenient; each project must also pin its own development dependency below.

Use the PHP, Composer, Node.js, and npm tools provided by your chosen environment. Match runtime and database versions to each project's requirements.

## Project setup

For each project, link or park it in Flock/Herd, enable local HTTPS, and configure its local database connection in `.env`. Follow the project's README for dependency installation, migrations, frontend assets, and checks.

### Pun and release checks (required)

Install the tested version as a development dependency so every developer uses the same executable:

```bash
composer require --dev shipfastlabs/pun:0.4.3
vendor/bin/pun --version
```

The first project-local run downloads the matching native binary and verifies its checksum; with a signed-in GitHub CLI it also verifies the build attestation. Production uses `composer install --no-dev` and does not need Pun.

1. For an existing Pint project, run `vendor/bin/pun migrate --from pint --dry-run`, review its output, then run it without `--dry-run`. For a new project, use `vendor/bin/pun init`. Commit `pun.toml` and both Composer files.
2. Set `[php] version` to the production PHP target. Preserve the project's formatting conventions and select its Laravel/test extensions. Use `[files] paths = ["."]` and exclude reference/generated directories as appropriate. Do not explicitly exclude `vendor`: Pun already skips dependency diagnostics, and a global exclusion prevents its analyser from discovering dependency symbols.
3. Keep PHPStan/Larastan installed and preserve `composer types:check` and `phpstan.neon`. Pun's analysis is supplementary while its framework coverage is incomplete. Match its analysis paths to the existing PHPStan paths; the current projects use `preset = "phpstan/level_7"`.
4. Review the first full `vendor/bin/pun check`. Existing analysis findings may be baselined only with the user's approval: generate `pun-baseline.toml`, set `[analyse] baseline = "pun-baseline.toml"`, and commit it. A baseline records accepted initial debt, not proven false positives. New findings must be fixed; do not automatically regenerate the baseline.
5. Configure the Composer scripts below. Keep the project's existing tests, frontend checks and build in its release gate. Run `composer predeploy` before any deployment.
6. Add `shipfastlabs/pun` to the `packages` array in `boost.json`, preserving existing entries. Add `pint/core` to `config/boost.php`'s excluded guidelines. Use `.ai/guidelines/shipfastlabs/pun/core.md` to override Pun's bundled guidance: require the project-local binary, retain Larastan and Composer, review fixes locally, and require the release gate. Run `composer boost:update` and verify that `AGENTS.md` and the selected agents' `pun-development` skills are refreshed.

Use these script names consistently:

```json
{
    "lint": ["pun check --fix"],
    "lint:check": ["pun check"],
    "security:check": [
        "@composer audit --locked --no-interaction",
        "npm audit --package-lock-only --audit-level=low"
    ],
    "predeploy": [
        "Composer\\Config::disableProcessTimeout",
        "@composer validate --no-check-publish",
        "@composer install --no-interaction --prefer-dist",
        "npm ci",
        "@security:check",
        "@ci:check",
        "npm run build"
    ]
}
```

Merge this example into the existing scripts; do not replace them wholesale. `ci:check` must include `lint:check`, `types:check`, the PHP suite and all relevant frontend checks/tests. Add missing frontend checks to `predeploy`, and omit the extra build if `ci:check` already builds. Both package managers must install from committed lockfiles. Dependency updates remain deliberate, reviewed work using Composer/npm.

During development, run `vendor/bin/pun check --dirty` after PHP edits. Apply and review fixes with `vendor/bin/pun check --fix --dirty`. Full release checks are read-only for source code; never run automatic fixes on production.

### Dependency security (required)

Run `composer security:check` before releases and at least weekly for idle apps. Include development and indirect dependencies. If the Composer audit fails, the combined script stops; run `npm audit --package-lock-only --audit-level=low` separately to see frontend findings too.

Treat an unavailable audit service as a failed check, not a clean report. Review advisories, update affected packages in a working branch, commit the lockfiles and rerun the full checks. Do not use `npm audit fix --force`, broad exclusions, or automatic production updates to bypass a failure.

The [basic deployment script](deploy.sh) and [Deployer wrapper](deployer/deploy.sh) call `composer predeploy` locally before pushing or contacting production. Prepared-release activation reruns the gate and verifies that the prepared commit matches the locally checked commit. Tests must use isolated test databases and fake external services.

### GitHub automation (optional, later)

The optional “all-out best” setup adds GitHub-hosted CI, required PR checks, scheduled audits, Dependabot update PRs and deployment after successful checks. Selective auto-merging can follow once test coverage is dependable. Start with STT when this work is requested; the normal setup above has no scheduled scanner or automatic updater.

### Laravel Brain (optional)

Recommend [Laravel Brain](https://github.com/laramint/laravel-brain) for exploring the app's architecture. If wanted:

- Install: `composer require --dev laramint/laravel-brain`.
- Set `LARAVEL_BRAIN_AUTO_DISCOVER_ROUTES=true` in the local `.env`.
- Run `php artisan brain:scan`. Auto-discovery also applies to the viewer's **Re-scan** button.
- Recommend connecting Brain's MCP server in Codex and Claude Code: `php artisan mcp:start brain`. Install `laravel/mcp` as a dev dependency if missing.

### Cloudflare trusted proxies

For sites behind Cloudflare, configure [trusted proxies](https://laravel.com/docs/requests#configuring-trusted-proxies) so `$request->ip()` resolves the visitor IP for rate limiting, logging, and IP-based logic.

In `bootstrap/app.php`, import `Illuminate\Http\Request`. Add this first inside the existing `withMiddleware` callback:

```php
$middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR);
```

Use `'*'` only when the origin accepts traffic exclusively through Cloudflare. If directly accessible, pass an array of [Cloudflare's published IPv4 and IPv6 ranges](https://www.cloudflare.com/ips/) to `at` instead, and keep it current.
