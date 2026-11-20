# Zero-downtime Laravel deployment

Use Deployer 8 with release directories and one atomic `current` symlink.
Daily deployment is one command:

```bash
./deploy.sh
```

This guide targets Laravel sites on Linux VPS accounts with Apache/PHP-FPM, MySQL and SSH access.
The examples use AlmaLinux, WHM/cPanel and EasyApache PHP paths. Adapt the paths and versions to the server.
Before relying on zero-downtime deployment, check public health, assets and PHP revision responses during a release switch.
For apps with background tasks, also check worker restarts and scheduler execution.
The first conversion from an old web directory can briefly interrupt requests. Later deployments switch only `current`.

## Copy the recipe

Copy the contents of [deployer/](deployer/) into the Laravel project.
Merge [public.htaccess.example](deployer/public.htaccess.example) with the application's `public/.htaccess`.
The added rules enforce HTTPS and block HTTP access to PHP INI files.

Install the tested Deployer version:

```bash
composer require --dev deployer/deployer:8.0.5
chmod +x deploy.sh
```

Edit `deploy.php` for the site:

| Setting | Example |
| --- | --- |
| Host / SSH account / port | `203.0.113.10` / `example` / `2200` |
| Deployment directory | `/home/example/app` |
| Public directory | `/home/example/public_html` |
| PHP executable | `/opt/cpanel/ea-php83/root/usr/bin/php` |
| Composer executable | `/home/example/bin/composer` |
| Public URL | `https://example.com` |
| Expected database | `example_laravel_production` |
| Queue worker | `false` for sites without a worker |

This recipe expects Laravel, MySQL, Vite, `npm ci`, `npm run build`, a `main` branch and an `origin` remote.
It runs pending Laravel migrations. It is not a generic plain-PHP or no-frontend recipe.
The server receives a built archive. It needs neither Node nor a GitHub SSH key.

Add these entries to `.gitignore`:

```gitignore
/.env.production
/.env.deploy
/public/build/
```

Check `git ls-files public/build` too: adding an ignore rule does not untrack existing files.
If it lists generated assets, run `git rm --cached -r public/build` and commit that change; local files stay in place.
The deploy wrapper uploads the fresh build separately from the source archive.

## Prepare the hosting account

Set up SSH keys and the expected host key on the deployment machine.
Run the account bootstrap:

```bash
ssh -p 2200 example@203.0.113.10 'bash -s' < deploy/setup-server.sh
```

The script checks and installs Composer, then creates private release directories.
It retains existing Composer and directories. It does not change the database or web routing.
Optional arguments select another PHP executable and app directory.
Composer uses a command-specific `allow_url_fopen=1` setting.

The script also checks systemd lingering. Basic sites without background workers can ignore that result.

## Use separate database users

Use `$account_laravel_production` for the production database and `$account_laravel_local` for local development.
`$account` means the cPanel account name: for example, `example_laravel_production` and `example_laravel_local`.
Create the local database on your development machine.

Grant privileges only on the application's database:

| User | Privileges |
| --- | --- |
| `example_web` | SELECT, INSERT, UPDATE, DELETE |
| `example_deploy` | The four above, plus CREATE, ALTER, DROP, INDEX, REFERENCES |

Put `example_web` and its password in the production application environment.
Create a private local file for migration credentials:

```bash
(umask 077; cp deploy/credentials.env.example .env.deploy)
```

Edit `.env.deploy`:

```dotenv
DEPLOY_DB_USERNAME=example_deploy
DEPLOY_DB_PASSWORD='your-password'
```

Use dotenv quoting for special characters. Do not upload this file.
An alternative runner can set `DEPLOY_CREDENTIALS_FILE` to a private dotenv file.
The recipe requires private file permissions, such as `chmod 600`.

Deployer passes migration credentials through SSH stdin and hides the payload in deployment logs.
The migration process overrides its database credentials in memory. The web configuration cache retains the restricted user.
The helper rejects database URLs and separate read/write connections. Those configurations need an adapted helper.
This separation limits routine web privileges. It does not isolate credentials from a compromised server during deployment.

## Configure the production environment

Create `.env.production` from the application's environment template.
Set `APP_ENV=production`, `APP_DEBUG=false`, the HTTPS URL, database details and integration credentials.
Use one stable production `APP_KEY`. Preserve an existing key.
For database-backed workers, use the shared database cache for restart signals.

Upload the environment once:

```bash
ssh -p 2200 example@203.0.113.10 'umask 077; set -C; cat > /home/example/app/shared/.env' < .env.production
```

**`set -C` refuses to overwrite an existing file.**

### Update environment variables after launch

Edit the local `.env.production`. From the local project directory, run:

```bash
scp -P 2200 .env.production example@203.0.113.10:/home/example/app/shared/.env
./deploy.sh
```

Replace the example account, host, port and path with the site values.
`scp` uses uppercase `-P` for the SSH port and overwrites the entire destination file.
Keep `.env.production` complete and current before upload. Preserve the production `APP_KEY`.
The initial setup creates the server file with private permissions (0600).

Laravel caches environment values. Uploading the file alone does not refresh the active release.
Deployment rebuilds the configuration cache and signals enabled queue workers to restart.
`./deploy.sh` does not upload `.env.production` automatically.

If you edit `/home/example/app/shared/.env` directly on the server, run `./deploy.sh` afterward.
Keep the local environment and server environment consistent.

## Configure Apache and PHP-FPM once

The tested layout keeps the existing primary-domain document root:

```text
/home/example/public_html -> /home/example/app/current/public
/home/example/app/current -> releases/005
/home/example/app/releases/005/
/home/example/app/shared/
    .env
    storage/
    build-assets/
    public/.user.ini
    public/php.ini
    public/.well-known/
```

Check both HTTP and HTTPS virtual hosts before the first launch.
Apache must allow the symlinks. Symlinks and targets must belong to the site account.
PHP-FPM must run as that account and allow access to the private app directory.
If the pool uses `open_basedir`, its allowed paths must include the private app directory.
`open_basedir` disables PHP's realpath cache. Check cache behavior for the actual pool configuration before launch.

In cPanel, open **MultiPHP INI Editor → Editor Mode → the domain**.
Retain existing settings and add:

```ini
opcache.validate_timestamps = 1
opcache.revalidate_freq = 0
opcache.revalidate_path = 1
```

**Check `.user.ini`, not only `php.ini`.**
The editor can save settings in `php.ini` without updating the effective PHP-FPM values.
If the settings are missing from `.user.ini`, add them to the shared file and check PHP-FPM again.
The Home Directory editor is unnecessary for this layout.
User INI settings can take five minutes to refresh in an existing request path.

Create `app/shared/public` as the site account.
Copy existing public `.user.ini`, `php.ini` and `.well-known` into their matching shared paths.
Add the three OPcache settings to the shared `.user.ini` directly if the editor omitted them.
Preserve other settings and review existing destinations before overwriting them.
Deployer links these shared paths into every release.

**After deployment, edit the shared `.user.ini` directly over SSH.**
cPanel's MultiPHP INI Editor rejects the symlinked `public_html/php.ini` with “cannot be a symbolic link.”
Use `/home/example/app/shared/public/.user.ini` for supported per-site directives, including upload limits.
Keep the existing OPcache settings. Do not replace the release symlinks with regular files.
Changes persist across deployments and can take five minutes to reach existing PHP-FPM request paths.
Some directives require server or FPM pool configuration instead of `.user.ini`.

The optional `deploy/check-fpm.py` checks the real FPM pool with disposable synthetic releases:

```bash
python3 deploy/check-fpm.py /path/to/site-fpm.sock /home/example/app/shared /home/example/app/shared/public/.user.ini
```

Run it on the server as the account user. It needs Python 3 and access to the FPM socket.
The probe expects `open_basedir` to be enabled and the three OPcache values shown above.
For a different pool configuration, adapt the probe and check release-switch behavior before launch.
It loads no Laravel application and changes no application data or live web routing.
It checks FPM behavior. The public HTTP checks still need to pass after launch.

References: [PHP user INI files](https://www.php.net/manual/en/configuration.file.per-user.php), [OPcache settings](https://www.php.net/manual/en/opcache.configuration.php), [realpath cache](https://www.php.net/manual/en/ini.core.php#ini.realpath-cache-size), [cPanel editor](https://docs.cpanel.net/cpanel/software/multiphp-ini-editor-for-cpanel/).

## Check upload limits before launch

For apps with uploads, check the effective PHP-FPM limits before launch.
CLI PHP can use different configuration.
PHP defaults to a 2 MB file limit and an 8 MB request limit, unless the host overrides them.
These defaults can reject files that Laravel accepts.

Set limits for the application in `/home/example/app/shared/public/.user.ini`.
For example:

```ini
upload_max_filesize = 50M
post_max_size = 64M
```

`upload_max_filesize` limits each file. `post_max_size` limits the entire request, including all files and form fields.
Choose a request limit above the combined size of permitted files, with room for form data.
The example does not permit multiple 50 MB files in one request.
Check `max_file_uploads` against the permitted file count. The default of 20 is sufficient for many apps.
Check web-server, proxy and provider limits too.

Direct browser uploads to R2 bypass PHP upload limits, but avatars and inbound email attachments can still pass through PHP.
For direct uploads, check bucket CORS for the production origin, upload method and request headers.

After changes, allow up to five minutes for `.user.ini` refresh and check the effective PHP-FPM values again.
The shared file persists across deployments. No root command is required for these two directives in this setup.

Reference: [PHP upload configuration](https://www.php.net/manual/en/ini.core.php#ini.upload-max-filesize).

## Check edit and delete requests before launch

A working login, `/up`, or asset response does not prove that writes work.
Some hosting configurations divert native `PUT`, `PATCH` or `DELETE` requests before PHP handles them.
One symptom is Laravel reporting “GET method is not supported” after the client sent DELETE.
This is a hosting/request compatibility issue, not a Laravel configuration or Deployer switch.
Check the actual domain; do not assume every account on the same server behaves identically.

Use an existing mutation-only route with a nonexistent ID and no authentication to compare the native verb with an override:

```bash
# Replace this path with a real DELETE-only route and a guaranteed nonexistent ID.
curl -i -X DELETE -H 'Accept: application/json' https://example.com/uploads/00000000-0000-0000-0000-000000000000
curl -i -X POST -H 'Accept: application/json' -H 'X-HTTP-Method-Override: DELETE' https://example.com/uploads/00000000-0000-0000-0000-000000000000
```

Do not follow redirects during diagnosis. Compare the response body, status and access logs: a wrong-method 405 is different from an expected CSRF/auth rejection. A route that also accepts GET can hide the problem behind a login response. These probes verify routing only; use isolated test data to verify successful edits/deletes and denied access. Keep real production records, files and mail out of smoke tests.

If the host mishandles native verbs, correct its domain-specific configuration or use Laravel's standard POST method override in application requests:

```js
// Inertia: keep its normal CSRF handling, payload and visit callbacks.
router.post(url, data, {
    headers: { 'X-HTTP-Method-Override': 'PATCH' },
});

// Plain fetch: retain the application's existing CSRF/session handling.
fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-HTTP-Method-Override': 'DELETE',
    },
});
```

Use the intended verb (`PUT`, `PATCH` or `DELETE`) in the override. Keep existing backend routes, policies and CSRF protection. Merge other headers and preserve any JSON/form payload and error handling. Ordinary forms may use `@method(...)` or `_method`; Wayfinder `.form()` helpers already generate POST overrides where needed. No change is required for correctly working POST/GET calls or Livewire actions.

Apply this only to the application's own Laravel endpoints. Signed direct-to-storage PUTs must keep their signed method, headers and body. Add regression coverage for the actual submit/delete controls, request payload, errors and authorization, then rebuild and deploy assets. Refresh existing browser tabs to load the fix.

References: [Laravel method spoofing](https://laravel.com/docs/13.x/routing#form-method-spoofing), [Inertia custom headers](https://inertiajs.com/docs/v3/the-basics/manual-visits#custom-headers).

## Optional queue worker

Skip this section for sites without queued work. Leave `queue_worker=false` in `deploy.php`.
Otherwise, set `queue_worker=true` and adapt `deploy/queue.service.example` for the account and PHP executable.

As the site account, check lingering:

```bash
loginctl show-user "$(id -un)" -p Linger
```

If the result is `Linger=no`, run this once as root:

```bash
loginctl enable-linger example
```

Lingering starts the user manager at boot and keeps it available after logout.
It persists across reboots. It applies once per account, not once per site or deployment.

Upload the template to `~/app/shared/example-queue.service`.
As the site account, install it without starting it:

```bash
mkdir -p ~/.config/systemd/user
install -m 0644 ~/app/shared/example-queue.service ~/.config/systemd/user/example-queue.service
systemd-analyze --user verify ~/.config/systemd/user/example-queue.service
systemctl --user daemon-reload
```

After the first launch:

```bash
systemctl --user enable --now example-queue.service
systemctl --user status example-queue.service --no-pager
```

Normal deployments request `queue:restart`. The worker finishes its current job, then systemd starts it through the new `current` link.
Manual service changes use `systemctl --user`. Root is unnecessary.
Use `journalctl --user -u example-queue.service` for service logs.
Do not run duplicate system-level and user-level workers.

The template uses a 60-second job timeout. Keep the queue's `retry_after` longer than that timeout.
Retain old releases until workers and in-flight requests finish.

## Optional scheduler

Skip the scheduler for sites without scheduled tasks.
Otherwise, adapt `deploy/scheduler.cron.example` and add its line to the account's crontab after launch.
Use one scheduler per app. Replace old cleanup jobs for that app and preserve unrelated entries.
Include `shared/storage/logs/scheduler.log` in the server's log rotation policy.

## First launch

Prepare the release:

```bash
./deploy.sh --prepare
```

The command commits changes interactively, builds assets, pushes that exact commit and uploads the release.
It installs production dependencies, creates caches and checks the database and assets.
It does not run migrations or change the live web root.

Check the existing database, pending migrations and recovery plan before publication.
Stop the old app and its scheduler at the agreed cutover.
Publish the prepared release:

```bash
./deploy.sh --activate
```

Activation runs pending migrations and selects the prepared release.
It preserves the old directory as `public_html.before-laravel`, then installs the fixed public symlink.
It refuses an unexpected existing symlink or backup conflict.
If symlink installation fails, it restores the old web directory.
The initial directory conversion is not atomic. Subsequent `current` switches are atomic.

HTTP checks compare static and PHP-executed revision markers, request `/up`, and fetch entry assets. Complete the [edit/delete request checks](#check-edit-and-delete-requests-before-launch) separately; these health checks do not exercise mutations.
A failed preparation leaves the active release unchanged.
A failure after publication reports the error and leaves the selected release in place.
There is no automatic database or code rollback.

Start the optional worker and scheduler after launch.
Enable application integrations with their required credentials.
Do not infer successful mail delivery or storage writes from configuration checks alone.

## Daily deployments and other runners

```bash
./deploy.sh
```

The same command works from a Mac or a Linux/Orb runner with PHP, Composer, Node/npm, Git, SSH and rsync.
Each runner needs SSH access, the expected host key and its private migration credentials.
Noninteractive runs require a clean, committed checkout. The wrapper rejects source changes during the build.
Do not deploy concurrently from separate runners. The recipe also enforces a remote lock.

No `artisan down/up`, root command or FPM restart is part of the tested routine deployment.
Each release retains its own manifest and compiled views. Old hashed assets stay available in shared storage.
The recipe currently retains all releases and hashed assets. Monitor disk usage and define cleanup before storage becomes scarce.

Zero-downtime code publication still needs compatible database changes, queued jobs and shared cache/session formats.
Use additive schema changes first. Remove old columns only after old code and jobs no longer use them.
Code rollback does not restore the database.

[Basic deployment](basic-deployment.md) · [Queue worker setup](queue-worker-setup.md)
