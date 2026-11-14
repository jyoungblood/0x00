#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

case "${1:-}" in
    '') deploy_task=deploy ;;
    --prepare) deploy_task=release:prepare ;;
    --activate) deploy_task=release:activate ;;
    *) echo "Usage: ./deploy.sh [--prepare|--activate]" >&2; exit 1 ;;
esac
if [[ $# -gt 1 ]]; then
    echo "Usage: ./deploy.sh [--prepare|--activate]" >&2
    exit 1
fi
if [[ ! -f vendor/bin/dep ]]; then
    echo "Run composer install first (including development dependencies)." >&2
    exit 1
fi
export DEPLOY_LOCK_TOKEN
DEPLOY_LOCK_TOKEN="$(php -r 'echo bin2hex(random_bytes(16));')"
if [[ "$deploy_task" != release:prepare ]]; then
    php deploy/read-credentials.php
fi
if [[ "$deploy_task" == release:activate ]]; then
    exec php vendor/bin/dep "$deploy_task" production
fi
if [[ "$(git branch --show-current)" != main ]]; then
    echo "Switch to main before deploying." >&2
    exit 1
fi

# Dirty trees require an interactive commit; already-committed CI/Orb builds can run unattended.
if [[ -n "$(git status --porcelain)" ]]; then
    if [[ ! -t 0 ]]; then
        echo "Commit changes before deploying noninteractively." >&2
        exit 1
    fi
    git status --short
    read -r -p 'Commit message (Enter for timestamp; Ctrl-C to cancel): ' commit_message
    git add --all
    git commit -m "${commit_message:-Updates - $(date '+%Y-%m-%d %T')}"
fi
revision="$(git rev-parse HEAD)"
npm ci
npm run build
test -s public/build/manifest.json
test -d public/build/assets
if [[ -n "$(git status --porcelain)" || "$(git rev-parse HEAD)" != "$revision" ]]; then
    echo "Tracked source changed during the build; review and rerun." >&2
    exit 1
fi
git push origin "$revision:refs/heads/main"

DEPLOY_ARTIFACT_DIR="$(mktemp -d "${TMPDIR:-/tmp}/laravel-deploy.XXXXXX")"
export DEPLOY_ARTIFACT_DIR
trap 'rm -rf -- "$DEPLOY_ARTIFACT_DIR"' EXIT
git archive --format=tar --output="$DEPLOY_ARTIFACT_DIR/source.tar" "$revision"
cp -R public/build "$DEPLOY_ARTIFACT_DIR/build"
find "$DEPLOY_ARTIFACT_DIR/build" -type d -exec chmod 755 {} +
find "$DEPLOY_ARTIFACT_DIR/build" -type f -exec chmod 644 {} +
printf '%s\n' "$revision" > "$DEPLOY_ARTIFACT_DIR/REVISION"
php vendor/bin/dep "$deploy_task" production --revision="$revision"
