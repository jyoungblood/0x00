---
title: Deployment
sidebar:
  order: 4
---
🚜

---

break this page into several pages? (this is MOSTLY the VPS guide)
- [ ] break out deploy docs into separate pages
  - new cleaned up doc for pm2 setup

(specifically for dw on vps) - astro SSR deploy w/ ssh for pm2



dw docs - YOU probably want to use railway or render or whatever (pretty easy to deploy to those platforms)
BUT
if you, like me, have a private vps with ssh (whm/cp), and want to follow some different instructions, it's actually pretty easy and fast to get it set up with pm2 on your machine

it may seem daunting at first glance, but you can realistically do it in like 15 min…def less than an hour
and i'm sure YOU could do it even faster, since you're so smart




ALSO NOTE - if you have a vps where you're running multiple dw apps (or node sites), it's best to set up pm2 in 1 place (like whm reseller account) ... much easier to only have to fuck w/ the pm2 systemctl setup once than to have to do it for EVERY site 🙃


----

- [ ] set up db on production server
- [ ] set up SMTP & other services for production

- [ ] (below process w/ node/pm2 bs)

- [ ] deploy.sh
- [ ] monitor.sh


vps guide

idk about serverless / platform deployments (cf workers, vercel, etc) ... and honestly i don't give a shit, at least not right now

we're deploying on a private vps like our grandparents used to do

astro using node ssr adapter...how hard could it be?

dw deployment guide
- (see server ref vps astro deployment instructions)
- best practices: make sure you're backing up your db
	- ref (good idea) - s3mysqlbackup solution (purhost, reininghost, eqkh)
		- s3 media/static files backup script too (using on rh & purhost)


honestly i'm super stoked at how easy this can be to set up on a vps. you just need ssh & node (nvm & pm2, in my case...super easy and super reliable)
this will work on "modern" infra too (host it with vercel or cloudflare, idgaf)




?? where do you deploy it? wherever the f you want
- railway, render (or like netlify, vercel, fly, etc, idgaf)
- your own vps!


---


0x00 deploy & ssh user setup - link / solution in dw docs in general purpose format



misc dw deployment
- https://github.com/jyoungblood/0x00
- https://github.com/jyoungblood/0x00-astro-deploy


nvm - https://github.com/nvm-sh/nvm?tab=readme-ov-file#installing-and-updating
pm2 - https://pm2.io/docs/runtime/guide/installation/








KH VPS DEPLOYMENT (easy)

(root)
- [x] ssh keys
  - cp -R /home/hxgf/.ssh /home/dwsite
  - chown -R dwsite:dwsite .ssh

(ssh user)
- [ ] set up site on server
  - [x] git clone git@whatevergithuburl
    - (already have ssh keys set up)
  - [ ] npm install
  - [ ] npm run build

- [x] codebase update
  - [x] deploy.sh
    - touch deploy.sh && echo "\n/deploy.sh" >> .gitignore && chmod +x deploy.sh
    - touch monitor.sh && echo "\n/monitor.sh" >> .gitignore && chmod +x monitor.sh
  - [x] add ecosystem.config.cjs (for pm2)

- [x] add production .env 
- [x] add .htaccess to docroot

- [x] nvm install
  - curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
  - node exe path: /home/dwsite/.nvm/versions/node/v22.20.0/bin/node

- [x] pm2 install
  - npm install pm2 -g

- [ ] pm2 config
  - [ ] start app using ecosystem
    - pm2 start ecosystem.config.cjs 
    - pm2 save
  - [ ] add to systemctl



-----



for this example, let's say the app is called dwsite



## node apps (astro, esp) on kh vps
- ssh setup for user (not root)
- ssh as user
  - install nvm for user
  - install pm2 for user
  - add all the ecosystem.config stuff (pm2 config) for astro locally (ok to put in repo, shouldn't ever need to change tho)
  - add codebase via git (set up .env w/ correct production settings)

ecosystem.config.cjs
```js
module.exports = {
  apps: [
    {
      name: "dwsite",
      cwd: "/home/dwsite/dwsite_astro",
      script: "dist/server/entry.mjs",
      instances: 1,
      exec_mode: "fork",
    // to run in cluster mode:
      // instances: 2, // or "max"
      // exec_mode: "cluster", // switch from fork → cluster
      watch: false,
      interpreter: "/home/dwsite/.nvm/versions/node/v20.19.4/bin/node",
      env: {
        NODE_ENV: "production",
        HOST: "127.0.0.1",
        PORT: "6969",
        // Increase Node.js body size limits for large uploads
        NODE_OPTIONS: "--max-http-header-size=32768 --max-old-space-size=2048",
      },
      max_memory_restart: "1G",
      error_file: "logs/error.log",
      out_file: "logs/out.log",
      log_file: "logs/combined.log",
      time: true,
    },
  ],
}; 
```



- pm2 start ecosystem.config.cjs
  - pm2 save
  - pm2 startup
    - [run the command it gives you as root]
    - will be something like `sudo env PATH=$PATH:/home/dwsite/.nvm/versions/node/v20.19.4/bin /home/dwsite/.nvm/versions/node/v20.19.4/lib/node_modules/pm2/bin/pm2 startup systemd -u dwsite --hp /home/dwsite`
    - use the "bin" version of pm2 (ex, replace '/home/dwsite/.nvm/versions/node/v20.19.4/lib/node_modules/pm2/bin/pm2' with '/home/dwsite/.nvm/versions/node/v20.19.4/bin/pm2')
      - remove the `lib/node_modules/pm2/` from the pm2 binary path
      - use the same path that's shown if you run `which pm2` as the cpanel user
      - fyi this hard-codes the node version (that pm2 uses), so if you update it at any point, you'll need to update the systemd service as well
    - [ ] would LOVE to have a little script/utility where you enter the name of your app (as it is in pm2) and your node/pm2 binary path and it generates the CORRECT commands
      - ?? can we do that with the info in the ecosystem file?
      - ?? npm run pm2-startup
    - more info on configuring pm2 startup: https://pm2.keymetrics.io/docs/usage/startup/
    - (troubleshooting) ugh this part sucks, i hate it
      - systemctl status pm2-dwsite -- if it gives you shit:
        - systemctl stop pm2-dwsite || true
        - ```
            # replace lib/node_modules/... with bin/pm2 on ExecStart/Reload/Stop
            sed -i 's#/home/dwsite/.nvm/versions/node/v20.19.4/lib/node_modules/pm2/bin/pm2#/home/dwsite/.nvm/versions/node/v20.19.4/bin/pm2#g' /etc/systemd/system/pm2-dwsite.service

            # add a small delay after start (only once; safe to re-run)
            grep -q '^ExecStartPost=' /etc/systemd/system/pm2-dwsite.service || \
              sed -i '/^ExecStart=.*resurrect/a ExecStartPost=/bin/sleep 1' /etc/systemd/system/pm2-dwsite.service

            # optional: reduce restart hammering
            grep -q '^RestartSec=' /etc/systemd/system/pm2-dwsite.service || \
              sed -i '/^Restart=on-failure/a RestartSec=1' /etc/systemd/system/pm2-dwsite.service
            ```
        - ```
          systemctl daemon-reload
          systemctl enable pm2-dwsite
          systemctl start pm2-dwsite
          systemctl status pm2-dwsite -l
          journalctl -u pm2-dwsite -n 50 --no-pager
          ```
  - pm2 save (run again, as user)
- .htaccess proxy
  ```bash
  DirectoryIndex disabled  # disable default redirect to index.php
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
  RequestHeader set X-Forwarded-Host "%{HTTP_HOST}e"
  RewriteRule ^/?(.*)$ http://localhost:3669/$1 [P,L]
  ```


updated htaccess proxy that 404s some wp & other cms probing attempts

```
DirectoryIndex disabled

RewriteEngine On

RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Drop obvious CMS probes
RewriteRule ^(wp-admin|wp-login|wp-includes|xmlrpc\.php|wlwmanifest\.xml|media/system/js|\.env) - [F]

# Fast 404s for specific paths
RewriteRule ^media/system/js/core\.js$ - [R=404,L]
RewriteRule ^media/wp-includes/wlwmanifest\.xml$ - [R=404,L]

RequestHeader set X-Forwarded-Host "%{HTTP_HOST}e"
RewriteRule ^/?(.*)$ http://localhost:6969/$1 [P,L]
```




---


and if it goes offline
- cd /home/dwsite/astro // (directory where the site is deployed on your server)
- su dwsite
- pm2 start ecosystem.config.cjs
- pm2 save





---



init deploy notes

- see lwoa [deploy.sh](http://deploy.sh "‌") script
- need to run `npm run init` (via cli) when installing the site on the server
  - ?? can we do this w/ cf/serverless?
  - OR should we recommend just dumping a copy of the dev db?
- all the pm2 stuff too for vps (ecosystem.config.cjs in lwoa)
  - [monitor.sh](http://monitor.sh "‌")

‌

field guide - deployment

- never thought i'd see the day when node apps are easier to deploy than php apps
- if you use cpanel do this (whatever the solution is)
- otherwise use pm2 on a vps
- or use railway or fly or the other modern node hosts
  - this shit is trivially easy to set up
  - git push and don't worry about it

‌

??

dw - "deploy to render" solution

- recipe or whatever to do a simple install on render
- instructions for how to work w/ this (ftp or set up your own env locally idk)
- add a button to the readme





---

touch deploy.sh && echo "\n/deploy.sh" >> .gitignore && chmod +x deploy.sh

(raw script here - https://github.com/jyoungblood/0x00/blob/master/deploy/deploy-astro-vps-ssh.sh)

./deploy.sh

```bash
#!/bin/bash

GIT_BRANCH="main"
COMMIT_MESSAGE="Updates - $(date +"%Y-%m-%d %T")"
SSH_USER="xxxx"
SSH_SERVER="xxxx"
SSH_PORT="xxxx"
DEPLOYMENT_PATH="/home/xxxx/xxxx"
PM2_APP_NAME="xxxx"

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
git push origin $GIT_BRANCH



## Pull on remote via ssh
ssh $SSH_USER@$SSH_SERVER -p $SSH_PORT -t "cd $DEPLOYMENT_PATH && git restore package-lock.json && git pull origin $GIT_BRANCH && npm install && npm run build && npm run migrate && pm2 reload $PM2_APP_NAME"



exit

```



touch monitor.sh && echo "\n/monitor.sh" >> .gitignore && chmod +x monitor.sh

./monitor.sh

```bash

#!/bin/bash

SSH_USER="dwsite"
SSH_SERVER="xxx.xxx.xxx.xxx"
SSH_PORT="22"

## just monitoring pm2 from the home dir 

## Pull on remote via ssh
ssh $SSH_USER@$SSH_SERVER -p $SSH_PORT -t "pm2 logs"

exit




```




----
----
----
----




### pm2 startup bullshit



(after i ran)


sudo env PATH=$PATH:/home/dwsite/.nvm/versions/node/v22.20.0/bin /home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2 startup systemd -u dwsite --hp /home/dwsite


systemctl enable pm2-dwsite
systemctl start pm2-dwsite
systemctl status pm2-dwsite



systemctl stop pm2-dwsite

# replace lib/node_modules/... with bin/pm2 on ExecStart/Reload/Stop
sed -i 's#/home/dwsite/.nvm/versions/node/v22.20.0/lib/node_modules/pm2/bin/pm2#/home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2#g' /etc/systemd/system/pm2-dwsite.service

  # add a small delay after start (only once; safe to re-run)
grep -q '^ExecStartPost=' /etc/systemd/system/pm2-dwsite.service || \
    sed -i '/^ExecStart=.*resurrect/a ExecStartPost=/bin/sleep 1' /etc/systemd/system/pm2-dwsite.service

  # optional: reduce restart hammering
grep -q '^RestartSec=' /etc/systemd/system/pm2-dwsite.service || \
  sed -i '/^Restart=on-failure/a RestartSec=1' /etc/systemd/system/pm2-dwsite.service



(from cgpt)


Good news: it’s actually **running fine**.

From your `systemctl status`:

* **Active: active (running)** ✅
* PM2 daemon: **“PM2 v6.0.13: God Daemon (/home/dwsite/.pm2)”**
* Your app process is up: `node /home/dwsite/dwsite_astro/dist/server/entry.mjs`

That earlier “failed because the service did not take the steps required…” line can show up when:

* you ran `start` once with an old/bad unit and then fixed it, or
* systemd expected a different service “Type” (fork/notify) but PM2 is **simple** and didn’t “notify” quickly.

The ASCII box-drawing gibberish (`<E2>...`) is just PM2’s table rendering in logs—harmless.

## Make it clean & stable (quick checklist)

1. **Reset any stale failed state** (optional, tidy):

```bash
sudo systemctl reset-failed pm2-dwsite
```

2. **Unit file sanity (recommended defaults)**
   Open `/etc/systemd/system/pm2-dwsite.service` and make sure it looks like this (adjust the Node path if you upgrade later):

```ini
[Unit]
Description=PM2 process manager
Documentation=https://pm2.keymetrics.io/
After=network.target

[Service]
Type=simple
User=dwsite
Environment=PM2_HOME=/home/dwsite/.pm2
Environment=PATH=/home/dwsite/.nvm/versions/node/v22.20.0/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin
LimitNOFILE=infinity
Restart=always
RestartSec=2
KillMode=control-group

# If you use pm2 save / resurrect (what you’re doing now):
ExecStart=/home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2 resurrect
ExecReload=/home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2 reload all
ExecStop=/home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2 kill

[Install]
WantedBy=multi-user.target
```

Then:

```bash
sudo systemctl daemon-reload
sudo systemctl restart pm2-dwsite
sudo systemctl status pm2-dwsite -l
```

3. **Confirm PM2 sees your apps (as the user)**

```bash
sudo -u dwsite env PATH=/home/dwsite/.nvm/versions/node/v22.20.0/bin:$PATH pm2 list
```

4. **If you change Node versions later** (nvm install/use):

* Re-run, as **dwsite**: `pm2 startup` (so it prints a new command with the new path)
* Then, as **root**, run that printed `sudo env ... pm2 startup ...` again, or update the `PATH` in the unit to the new version
* `pm2 save`, `systemctl daemon-reload`, `systemctl restart pm2-dwsite`

## Optional hardening / quality-of-life

* Add a **WorkingDirectory** line if you want all PM2 actions to assume a base path:

  ```ini
  WorkingDirectory=/home/dwsite
  ```
* If you ever move to an **ecosystem file** (nice for config):

  ```ini
  ExecStart=/home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2 start /home/dwsite/ecosystem.config.js --env production
  ExecStop=/home/dwsite/.nvm/versions/node/v22.20.0/bin/pm2 delete all
  ```

  (In that case you don’t need `resurrect`/`save`.)

---

If you still see a failure on `start` but it ends up “active (running)” immediately after, it’s just systemd being cranky with timing. The config above (Type=simple, Restart=always, proper PATH) usually clears that up.
