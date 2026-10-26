# basic deployment

Laravel on a WHM/cPanel VPS: local build → Git push → SSH pull → Composer → rsync → Artisan.
Updates use a short maintenance window.

Replace `example`, `example.com`, and `0.0.0.0` with your account, domain, and server.
Run server commands as the cPanel account, except the admin steps.

## One-time setup

- [ ] @WHM/cPanel — create the account, database, database user, DNS, and HTTPS certificate.
- [ ] Enable SSH access and keys. Give the server account access to pull the Git repository.
- [ ] Select the app's required PHP version in MultiPHP Manager. Our deployment used PHP 8.3.
  - Check `php -v` over SSH. CLI and PHP-FPM must have compatible versions and extensions.
- [ ] Put the repository on branch `main` in `/home/example/public_html`.
- [ ] Change the domain's document root to `/home/example/public_html/public` for HTTP and HTTPS.
  - For the account's main domain, follow [Change the document root in WHM](#change-the-document-root-in-whm).
  - For addon domains, use cPanel → Domains → Manage.
  - Keep `.env`, `vendor`, and the rest of the application outside the document root.
- [ ] Install Composer at `~/bin/composer` using the block below.
- [ ] If the app requires Imagick, open WHM → Software → Module Installers → PHP PECL → Manage.
  - Select the app's PHP version, then install `imagick`. [cPanel instructions](https://support.cpanel.net/hc/en-us/articles/360050028094-How-to-install-PHP-extensions-using-PECL-using-WHM)
  - Check `php --ri imagick`. Enable it for both CLI and PHP-FPM, with WebP support for photo processing.
  - The earlier deployment did not finish ImageMagick policy hardening. Have the admin review allowed formats and resource limits.
- [ ] Create the production `.env` in `~/public_html`.
  - Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://example.com`, and `SESSION_SECURE_COOKIE=true`.
  - Add production database, email, and storage credentials. If the app uses R2, add its R2 configuration.
  - Keep `.env` out of Git. Use `QUEUE_CONNECTION=sync` until queued jobs have a managed worker.
- [ ] Install PHP dependencies, then generate `APP_KEY` **only if it is empty**:

```bash
cd ~/public_html
php -d allow_url_fopen=1 ~/bin/composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan key:generate --force
```

Keep the same `APP_KEY` for every later deployment.

- [ ] Check that the account owns the app and PHP-FPM can write to `storage/` and `bootstrap/cache/`.
  - R2 does not remove this requirement. Our account owned both directories and ran its own PHP-FPM pool.
  - Existing writable permissions are enough. Do not change ownership to `nobody` or use `777`.
- [ ] If the app uses local public uploads, run `php artisan storage:link`. R2-only uploads do not need this.

## Change the document root in WHM

This is the API Shell process used on WHM 138. Sign in to WHM as **root**.
Example: account `example`, with primary domain `example.com`.

1. Check that `/home/example/public_html/public/index.php` exists.
2. Open **Server Configuration → Tweak Settings → System**.
3. Set **cPanel & WHM API shell (for developers)** to **On**, then save.
4. Open **Development → API Shell** (API Shell for WHM).
5. Select **WHM API version v1** and function **`set_primary_domain_docroot`**.
6. Under **Variables**, enter these key/value pairs. Click **Add** for the second row:

| Key | Value |
| --- | --- |
| `username` | `example` |
| `docroot` | `public_html/public` |

Use the actual cPanel username. It selects that account's primary domain, so there is no `domain` parameter.
The `docroot` value is relative to the account's home directory, without a leading slash.

7. Click **Submit**.
8. Check the response: `metadata.result: 1`, `metadata.reason: OK`, and `data.documentroot: /home/example/public_html/public`.
9. Open the domain over HTTP and HTTPS to check the site.

This changes the selected account's primary domain and its aliases. Other accounts keep their document roots.
Enabling API Shell alone does not change any document root. [WHM API Shell instructions](https://docs.cpanel.net/whm/development/api-shell-for-whm/)

## Install Composer once

Our server had neither `composer` on PATH nor `/opt/cpanel/composer/bin/composer`.
This installs it under the account, with a checksum check and a command-only `allow_url_fopen` override. [Official installer](https://getcomposer.org/download/)

```bash
(
set -e
mkdir -p "$HOME/bin"
cd "$(mktemp -d)"
curl -fsS https://getcomposer.org/installer -o composer-setup.php
curl -fsS https://composer.github.io/installer.sig -o composer-setup.sig
php -d allow_url_fopen=1 -r 'if (!hash_equals(trim(file_get_contents("composer-setup.sig")), hash_file("sha384", "composer-setup.php"))) { fwrite(STDERR, "Checksum verification failed\n"); exit(1); } require "composer-setup.php";' -- --install-dir="$HOME/bin" --filename=composer
)
```

## Deploy from your Mac

- [ ] Copy [deploy.sh](deploy.sh) and [monitor.sh](monitor.sh) into the app's local root.
- [ ] Set the SSH account, server, port, and paths in both scripts. The sample port is `2200`.
- [ ] Use the **Laravel recipe** in `deploy.sh`, including Composer, asset upload, migrations, and cache commands.
  - The generic Git push/pull script alone is not enough.
  - Keep `RESTART_QUEUE=0` until [queue workers](queue-worker-setup.md) are configured.
- [ ] Keep `.env`, `vendor/`, `node_modules/`, and `public/build/` ignored by Git.
- [ ] Review local changes. The script stages and commits **all** changes, then pushes `main`.
- [ ] Back up the production database before migrations. Run only one deployment at a time.
- [ ] Run `bash deploy.sh`.
  - Locally: `npm ci` → `npm run build` → check manifest → normalize permissions → commit/push.
  - Server: `down` → pull → Composer install → rsync → `config:clear` → `migrate --force` → `optimize` → optional `queue:restart` → `up`.
- [ ] Open the site and check login, styles, scripts, uploads, and email.
- [ ] Run `bash monitor.sh` to follow `storage/logs/laravel.log`.

Node stays on your Mac. Rsync uploads the full `public/build/` folder, including its manifest.
Old hashed assets stay available because the script does not use `rsync --delete`.

## Fixes from the first deployment

| Symptom | Fix |
| --- | --- |
| Missing `vendor/autoload.php` | Install Composer dependencies before Artisan commands. |
| Composer reports missing `ext-imagick` | Install Imagick for the correct PHP version. Do not use `composer update` or ignore the requirement. |
| Missing Vite manifest | Build locally and upload all of `public/build/`. Git ignores it. |
| Assets return 403 after rsync | Use directories `755` and files `644` in the local build. The script does this before rsync preserves permissions. |
| macOS rsync rejects numeric `--chmod` | Use the script's local `find`/`chmod` steps instead. |
| Artisan exits silently, with no Laravel log | Run the diagnostic command below over SSH. Keep web debugging off. |

```bash
php -d display_errors=1 -d display_startup_errors=1 -d error_reporting=-1 artisan --version
```

If deployment fails after maintenance starts, fix the error and rerun the script.
Maintenance stays enabled until deployment succeeds. Do not bring an incomplete deployment online.
`monitor.sh` follows Laravel's log only. Startup errors can appear in PHP-FPM or Apache logs.

Next: [zero downtime deployment](zero-downtime-deployment.md) · [queue worker setup](queue-worker-setup.md)
