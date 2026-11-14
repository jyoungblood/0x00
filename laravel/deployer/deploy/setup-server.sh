#!/usr/bin/env bash
set -euo pipefail
umask 027

# Run over SSH as the site's account, never root. Optional arguments: PHP binary, app root.
php_binary="${1:-/opt/cpanel/ea-php83/root/usr/bin/php}"
app_root="${2:-$HOME/app}"
composer_binary="$HOME/bin/composer"

if [[ "$(id -u)" == 0 ]]; then
    echo "Run this as the site's SSH account, not root." >&2
    exit 1
fi
if [[ ! -x "$php_binary" ]]; then
    echo "PHP executable not found: $php_binary" >&2
    exit 1
fi
case "$app_root" in
    "$HOME"/*) ;;
    *) echo "The app root must be inside the account's home directory." >&2; exit 1 ;;
esac
case "$app_root/" in
    *'/../'*|*'/./'*|"$HOME/public_html/"*|"$HOME/www/"*)
        echo "Choose a private app directory outside the web root." >&2
        exit 1
        ;;
esac
for directory in "$HOME/bin" "$app_root" "$app_root/releases" "$app_root/shared"; do
    if [[ -L "$directory" || ( -e "$directory" && ! -d "$directory" ) ]]; then
        echo "Expected a real directory or unused path: $directory" >&2
        exit 1
    fi
done

"$php_binary" -r 'if (PHP_VERSION_ID < 80300) { fwrite(STDERR, "PHP 8.3+ is required.\n"); exit(1); }'
command -v curl >/dev/null
command -v git >/dev/null
command -v rsync >/dev/null

if [[ -e "$composer_binary" || -L "$composer_binary" ]]; then
    if [[ ! -f "$composer_binary" || -L "$composer_binary" ]]; then
        echo "Expected an existing Composer file at $composer_binary" >&2
        exit 1
    fi
    echo "Keeping the existing Composer installation."
else
    setup_dir="$(mktemp -d)"
    trap 'rm -rf -- "$setup_dir"' EXIT
    (
        cd "$setup_dir"
        curl --fail --silent --show-error --location https://getcomposer.org/installer -o composer-setup.php
        curl --fail --silent --show-error --location https://composer.github.io/installer.sig -o composer-setup.sig
        "$php_binary" -r 'if (!hash_equals(trim(file_get_contents("composer-setup.sig")), hash_file("sha384", "composer-setup.php"))) { fwrite(STDERR, "Composer installer checksum failed.\n"); exit(1); }'
        "$php_binary" -d allow_url_fopen=1 composer-setup.php --2 --install-dir="$setup_dir" --filename=composer
        mkdir -p "$HOME/bin"
        install -m 0755 "$setup_dir/composer" "$composer_binary"
    )
fi

"$php_binary" -d allow_url_fopen=1 "$composer_binary" --version

# Deployer will populate shared storage and link .env when the first release is prepared.
# Leave existing directories and their permissions alone on repeated setup runs.
for directory in "$app_root" "$app_root/releases" "$app_root/shared"; do
    if [[ ! -d "$directory" ]]; then
        mkdir -m 0755 "$directory"
    fi
done

printf '\nReady: %s\nComposer: %s\n' "$app_root" "$composer_binary"

# Optional background workers need lingering; basic sites do not.
account_name="$(id -un)"
if command -v loginctl >/dev/null 2>&1 && linger_status="$(loginctl show-user "$account_name" -p Linger 2>/dev/null)"; then
    case "$linger_status" in
        Linger=yes)
            echo "User services: lingering already enabled; no root setup needed."
            ;;
        Linger=no)
            echo "User services: lingering is disabled. Only needed if this account will run background workers."
            printf 'Once as root: loginctl enable-linger %q\n' "$account_name"
            ;;
        *)
            echo "User services: lingering status unknown; check before adding background workers."
            ;;
    esac
else
    echo "User services: lingering check unavailable; check user systemd support before adding background workers."
fi

echo "No database, environment file, web root, cron or service configuration was changed."
