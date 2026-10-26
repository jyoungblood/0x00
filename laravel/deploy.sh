#!/bin/bash
set -euo pipefail

GIT_BRANCH="main"
COMMIT_MESSAGE="Updates - $(date +"%Y-%m-%d %T")"
SSH_USER="example"
SSH_SERVER="0.0.0.0"
SSH_PORT="2200"
DEPLOYMENT_PATH="/home/example/public_html"
# Enable only after queue workers are managed by Supervisor/systemd.
RESTART_QUEUE=0

cd "$(dirname "${BASH_SOURCE[0]}")"
trap 'echo "Deployment failed. If maintenance mode started, it stays on until the issue is fixed and deployment succeeds." >&2' ERR

if [[ "$(git branch --show-current)" != "$GIT_BRANCH" ]]; then
    echo "Switch to $GIT_BRANCH before deploying." >&2
    exit 1
fi

# Build on the Mac; Node is not needed on the server.
npm ci
npm run build
test -s public/build/manifest.json
# Normalize generated assets locally; rsync -a preserves these permissions.
# macOS openrsync does not support numeric --chmod rules.
find public/build -type d -exec chmod 755 {} +
find public/build -type f -exec chmod 644 {} +

git add --all
if ! git diff --cached --quiet; then
    read -r -p "Commit message (default: $COMMIT_MESSAGE): " NEW_MESSAGE
    git commit -m "${NEW_MESSAGE:-$COMMIT_MESSAGE}"
fi
DEPLOY_COMMIT="$(git rev-parse HEAD)"
git push origin "$GIT_BRANCH"

# Quote arguments for the remote shell; quoted heredocs expand only on the server.
remote_bash() {
    local remote_command
    printf -v remote_command 'bash -seu -o pipefail -- %q %q %q %q' \
        "$DEPLOYMENT_PATH" "$GIT_BRANCH" "$DEPLOY_COMMIT" "$RESTART_QUEUE"
    ssh -p "$SSH_PORT" "$SSH_USER@$SSH_SERVER" "$remote_command"
}

remote_bash <<'REMOTE'
cd "$1"
if [[ "$(git branch --show-current)" != "$2" ]]; then
    echo "Server checkout must be on $2." >&2
    exit 1
fi
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
    echo "Server checkout has tracked changes; resolve them before deploying." >&2
    exit 1
fi
test -f .env
test -f "$HOME/bin/composer"

php artisan down --render="errors::503"
git pull --ff-only origin "$2"
if [[ "$(git rev-parse HEAD)" != "$3" ]]; then
    echo "Server commit differs from the locally built commit; rerun deployment." >&2
    exit 1
fi
php -d allow_url_fopen=1 "$HOME/bin/composer" install \
    --no-dev --prefer-dist --no-interaction --optimize-autoloader
mkdir -p public/build
REMOTE

# Keep old hashed assets for pages already open in a browser.
rsync -az --delay-updates \
    -e "ssh -p $SSH_PORT" \
    public/build/ \
    "$SSH_USER@$SSH_SERVER:$DEPLOYMENT_PATH/public/build/"

# Only bring the application back after every deployment step succeeds.
# Maintain production database backups before running schema migrations.
remote_bash <<'REMOTE'
cd "$1"
test "$(git rev-parse HEAD)" = "$3"
test -s public/build/manifest.json
php artisan config:clear
php artisan migrate --force
php artisan optimize
if [[ "$4" == 1 ]]; then
    php artisan queue:restart
fi
php artisan up
REMOTE

echo "Deployed $DEPLOY_COMMIT successfully."
