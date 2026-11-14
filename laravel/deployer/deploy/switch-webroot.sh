#!/usr/bin/env bash
set -euo pipefail

# One-time first launch. Run as the site account, after current selects a checked release.
if [[ $# != 2 || "$(id -u)" == 0 ]]; then
    echo "Usage (as the site account): switch-webroot.sh PUBLIC_ROOT CURRENT_PUBLIC" >&2
    exit 1
fi
public_root="$1"
current_public="$2"
backup="${public_root}.before-laravel"
for path in "$public_root" "$current_public"; do
    case "$path" in
        "$HOME"/*) ;;
        *) echo "Both paths must be inside the site's home directory." >&2; exit 1 ;;
    esac
    case "$path/" in
        *'/../'*|*'/./'*) echo "Use absolute paths without dot segments." >&2; exit 1 ;;
    esac
done
case "$current_public/" in
    "$public_root/"*) echo "The release must be outside the existing web root." >&2; exit 1 ;;
esac
if [[ ! -f "$current_public/index.php" || ! -O "$current_public" ]]; then
    echo "The selected release must have an account-owned public directory and index.php." >&2
    exit 1
fi
if [[ -L "$public_root" ]]; then
    if [[ "$(readlink "$public_root")" == "$current_public" && -O "$public_root" ]]; then
        echo "Web root already follows current/public."
        exit 0
    fi
    echo "Web root is already a different symlink; refusing to replace it." >&2
    exit 1
fi
if [[ ! -d "$public_root" || ! -O "$public_root" || -e "$backup" || -L "$backup" ]]; then
    echo "Expected an account-owned web directory and an unused backup path: $backup" >&2
    exit 1
fi
temporary_directory="$(mktemp -d "${public_root}.switch.XXXXXX")"
restore_original=0
cleanup() {
    if [[ "$restore_original" == 1 && ! -e "$public_root" && ! -L "$public_root" ]]; then
        mv -T -- "$backup" "$public_root"
    fi
    rm -f -- "$temporary_directory/public"
    rmdir -- "$temporary_directory"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
if [[ -d "$public_root/.well-known" && -L "$current_public/.well-known" ]]; then
    rsync -a -- "$public_root/.well-known/" "$current_public/.well-known/"
fi
ln -s "$current_public" "$temporary_directory/public"
mv -T -- "$public_root" "$backup"
restore_original=1
if ! mv -T -- "$temporary_directory/public" "$public_root"; then
    echo "Symlink installation failed; restoring the original web directory." >&2
    exit 1
fi
restore_original=0
printf 'Web root now follows %s. Previous directory preserved at %s.\n' "$current_public" "$backup"
