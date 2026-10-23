# PM2 startup on a VPS (systemd / WHM reseller)

Use `equinox` as the example account. Replace it with your reseller username in every path and service name.

Run nvm, npm, and PM2 commands as the reseller. Use `sudo` only for system packages and systemd commands.

Prerequisites:

- SSH keys for the reseller account.
- nvm installed and loaded in the reseller shell.
- sudo access, or a root session for system changes.

## Install Node and create the symlink

Node 25+ Linux binaries require `libatomic`. If Node reports a missing `libatomic.so.1`, install the package for your OS:

```bash
# AlmaLinux / Rocky / RHEL / Fedora
sudo dnf install libatomic

# Ubuntu / Debian (use this instead)
sudo apt-get update && sudo apt-get install libatomic1
```

As the reseller:

```bash
nvm install 26 &&
nvm alias default 26 &&
npm install -g pm2 &&
ln -sfn "$(dirname "$(dirname "$(nvm which 26)")")" "$HOME/.nvm/node26"
```

The symlink points to the **whole Node installation directory**. It provides both `bin/node` and `bin/pm2`.

Check both commands before you change any app or service:

```bash
"$HOME/.nvm/node26/bin/node" --version
"$HOME/.nvm/node26/bin/node" "$HOME/.nvm/node26/lib/node_modules/pm2/bin/pm2" --version
nvm run default --version
```

Both Node commands must report the intended 26.x version.

## Set each app's interpreter

In each app's `ecosystem.config.cjs`, use:

```js
interpreter: "/home/equinox/.nvm/node26/bin/node",
```

The path has no extra `bin` between `.nvm` and `node26`.

Commit the ecosystem change locally and deploy it. The deploy template reloads the ecosystem file and runs `pm2 save`.
For a new app already on the server, run this from its directory:

```bash
pm2 startOrReload ecosystem.config.cjs --only APP_NAME && pm2 save
```

Replace `APP_NAME` with the ecosystem app name. A reload by app name alone does not read changes from the ecosystem file.

## Create the startup service

If the service already exists, skip this step and update its override instead.

```bash
sudo env PATH=/home/equinox/.nvm/node26/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin /home/equinox/.nvm/node26/bin/pm2 startup systemd -u equinox --hp /home/equinox
```

PM2 can resolve symlinks when it generates the base service. The override below supplies the stable paths.

## Add the systemd override

```bash
sudo systemctl edit pm2-equinox
```

Paste this into the editable section:

```ini
[Service]
Type=oneshot
RemainAfterExit=yes

# Clear PID tracking and automatic retries from the base unit.
PIDFile=
Restart=no

# Use the same Node installation for PM2 and its subprocesses.
Environment=
Environment=PATH=/home/equinox/.nvm/node26/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
Environment=PM2_HOME=/home/equinox/.pm2

ExecStart=
ExecStart=/home/equinox/.nvm/node26/bin/node /home/equinox/.nvm/node26/lib/node_modules/pm2/bin/pm2 resurrect
ExecReload=
ExecReload=/home/equinox/.nvm/node26/bin/node /home/equinox/.nvm/node26/lib/node_modules/pm2/bin/pm2 reload all
ExecStop=
ExecStop=/home/equinox/.nvm/node26/bin/node /home/equinox/.nvm/node26/lib/node_modules/pm2/bin/pm2 kill
```

This preserves the VPS setup that uses `Type=oneshot`. The expected service state is `active (exited)`.
PM2 manages the apps. This unit does not automatically restart the PM2 daemon if the daemon exits.

## Load and check the service

```bash
sudo systemctl daemon-reload
systemctl show pm2-equinox -p Environment -p ExecStart -p ExecReload -p ExecStop -p NeedDaemonReload
```

Check that all loaded paths use `.nvm/node26`. `NeedDaemonReload` must be `no`.
Old paths in the base unit are superseded by the override.

**The next command briefly interrupts all apps managed by this PM2 instance.**
As the reseller, save the current apps before the restart:

```bash
pm2 save && sudo systemctl restart pm2-equinox
systemctl status pm2-equinox --no-pager -l
pm2 ls
```

Check the sites after the restart. If startup fails, inspect the service and PM2 logs before another restart:

```bash
journalctl -u pm2-equinox -n 50 --no-pager
pm2 logs --lines 50 --nostream
```

## Future Node 26 updates

As the reseller, install the latest 26.x release and carry over global packages:

```bash
previous_node_version=$("$HOME/.nvm/node26/bin/node" --version) &&
nvm install 26 --reinstall-packages-from="$previous_node_version" &&
nvm alias default 26
```

Check Node and PM2 in the new installation before you move the symlink:

```bash
node --version
npm ls -g --depth=0
```

If installation succeeds and PM2 is present, update the symlink:

```bash
ln -sfn "$(dirname "$(dirname "$(nvm which 26)")")" "$HOME/.nvm/node26"
"$HOME/.nvm/node26/bin/node" --version
```

Save and restart PM2 with the commands in the previous section. Expect a brief interruption.
The systemd override and ecosystem paths stay the same. A symlink change alone does not require `daemon-reload`.
Keep the previous Node installation until the apps pass their checks.

For a future major version, create a separate symlink and update the default, ecosystems, and systemd override together.
