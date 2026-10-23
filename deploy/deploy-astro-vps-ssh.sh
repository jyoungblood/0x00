#!/bin/bash

GIT_BRANCH="main"
COMMIT_MESSAGE="Updates - $(date +"%Y-%m-%d %T")"
SSH_USER="xxxx"
SSH_SERVER="xxxx"
SSH_PORT="xxxx"
DEPLOYMENT_PATH="/home/xxxx/xxxx"
PM2_APP_NAME="xxxx"
# SITE_PATH="."

# alt option - deploy w/ rsync
# rsync version - will delete files/folders on server that aren't local (unless) specified
  # using .gitignore is more sustainable but git deploy is more involved setup
# add '-n' for dry run
# rsync -avhHP --delete-after --chmod=Du=rwx,Dg=rx,D=x,Fu=rwx,Fg=r,Fo=r --exclude '.env' --exclude '.well-known' --exclude 'cgi-bin' $SITE_PATH -e "ssh -p $SSH_PORT" $SSH_USER@$SSH_SERVER:$DEPLOYMENT_PATH

# (build step optional, uncomment to enable)

## Build FE assets
# npm run build

## Add new files to repo
git add --all

## Prompt for commit message (and provide a default)
echo "Enter Git commit message (default: $COMMIT_MESSAGE)"
read NEW_MESSAGE
[ -n "$NEW_MESSAGE" ] && COMMIT_MESSAGE=$NEW_MESSAGE
git commit -am "$COMMIT_MESSAGE"

## Push to origin branch
git push origin "$GIT_BRANCH" || exit $?

## Pull and build on the server.
# Set the server's nvm default during setup: nvm alias default 26
# Set the app interpreter in ecosystem.config.cjs to /home/USER/.nvm/node26/bin/node.
# Match the migration command to package.json (migrate or db:migrate).
# Reload the ecosystem file so interpreter changes take effect, then save for reboot.
ssh "$SSH_USER@$SSH_SERVER" -p "$SSH_PORT" -t "
  export NVM_DIR=\$HOME/.nvm
  . \"\$NVM_DIR/nvm.sh\" &&
  nvm use --silent default >/dev/null &&
  cd \"$DEPLOYMENT_PATH\" &&
  git restore package-lock.json &&
  git pull --ff-only origin \"$GIT_BRANCH\" &&
  npm ci &&
  npm run build &&
  npm run migrate &&
  pm2 reload ecosystem.config.cjs --only \"$PM2_APP_NAME\" &&
  pm2 save
"

exit
