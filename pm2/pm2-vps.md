setting up pm2 as a startup service (systemd) on a vps (whm reseller)

prerequisites: 
- set up the ssh keys so you can log in without having to enter the password
- set up sftp client for reseller acct too
- i like to have zsh set up as well
- install nvm and pm2 (as the reseller)



as reseller, run `pm2 startup` and you'll want to modify the command it gives you to use the correct node and pm2 paths

(you'll want to make a note what the paths are by running `which node` and `which pm2`)

updated command with the correct paths should look something like this:

`sudo env PATH=$PATH:/home/equinox/.nvm/versions/node/v24.13.1/bin /home/equinox/.nvm/versions/node/v24.13.1/bin/pm2 startup systemd -u equinox --hp /home/equinox`

THEN, run this command as root, and make the following modifications/overrides:
(use the override instead of editing the service file directly (would be overwritten by any future pm2 startup))

`sudo systemctl edit pm2-equinox`

and paste this:
```
# Notes:
# - this service is expected to be "active (exited)"
# - after adding/removing apps, run: pm2 save (as equinox)
# - update PATH/node paths when upgrading node

# If you ever want to confirm systemd is using your override as expected:
# systemctl show pm2-equinox -p Type -p RemainAfterExit -p PIDFile -p ExecStart -p Restart



[Service]
Type=oneshot
RemainAfterExit=yes

# kill PID expectations inherited from the base unit
PIDFile=

# stop systemd from retry-spamming a oneshot task
Restart=no

# ensure node is always resolvable
Environment=
Environment=PATH=/home/equinox/.nvm/versions/node/v24.13.1/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
Environment=PM2_HOME=/home/equinox/.pm2

# override the Exec* lines cleanly
ExecStart=
ExecStart=/home/equinox/.nvm/versions/node/v24.13.1/bin/node /home/equinox/.nvm/versions/node/v24.13.1/lib/node_modules/pm2/bin/pm2 resurrect
ExecReload=
ExecReload=/home/equinox/.nvm/versions/node/v24.13.1/bin/node /home/equinox/.nvm/versions/node/v24.13.1/lib/node_modules/pm2/bin/pm2 reload all
ExecStop=
ExecStop=/home/equinox/.nvm/versions/node/v24.13.1/bin/node /home/equinox/.nvm/versions/node/v24.13.1/lib/node_modules/pm2/bin/pm2 kill
```

(make sure all the node & pm2 paths match, and the app name is correct, replacing "equinox" with your reseller's name)


then reload and verify

`sudo systemctl daemon-reload`
`sudo systemctl reset-failed pm2-equinox`
`sudo systemctl restart pm2-equinox`
`systemctl status pm2-equinox --no-pager -l`


if you run into problems, phone a friend