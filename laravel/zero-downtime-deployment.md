# zero downtime deployment

**Future overview — not implemented by the included deploy script.** Update this document when we configure the first deployment.

Prepare a new release while the old release serves traffic. Then switch one symlink atomically.

```text
/home/example/app/
├── current → releases/003
├── releases/
│   ├── 002/
│   └── 003/
└── shared/
    ├── .env
    └── storage/
```

Each release has its own code, `vendor`, assets, and Laravel configuration caches.
Persistent files live outside releases. R2 uploads already do.

## Deployment recipe

- [ ] Build frontend assets locally for the exact Git commit.
- [ ] Create a new release directory for that commit.
- [ ] Link shared files, install Composer dependencies, upload assets, and build Laravel caches.
- [ ] Check the release and run migrations compatible with both old and new code.
- [ ] Atomically replace `current` with a symlink to the new release.
- [ ] Request a graceful worker restart and apply any required PHP-FPM cache refresh.
- [ ] Run HTTP checks for the expected release and its assets.
- [ ] Keep previous releases for rollback and active workers. Delete them only after they are no longer needed.

Routine deployments omit `artisan down/up`. Failed preparation leaves the current release online.
The script can automate these steps. Normal use stays `bash deploy.sh`.

## Decisions for the first setup

| Area | Plan |
| --- | --- |
| Web root | Point at `app/current/public`, or keep `public_html/public` as a fixed symlink to it. |
| Permissions | Keep links and targets owned by the account. Check Apache access, cPanel symlink protection, and `open_basedir`. |
| Database | Use staged changes: add → migrate code/data → remove later. Code rollback does not undo migrations. |
| Assets | Automatically retain older hashed CSS/JS in the served asset directory. Keep each new release's own manifest. |
| Workers | Run through `current`. Share the restart-signal cache. Keep old release files until old workers finish. |
| PHP caching | Check OPcache/path behavior once, then automate the required FPM refresh. CLI `opcache_reset()` does not reset FPM's cache. |
| Rollback | Switch back only when the previous code still works with the database and shared data. |

Database changes, queued-job changes, and shared cache/session formats need compatibility planning before deployment.
Laravel does not automatically detect every unsafe rollout.
An `AGENTS.md` rule and a deploy-time changed-file reminder can flag these changes for review.

Still to specify: release naming, atomic switch command, shared paths, asset retention, worker drain, FPM refresh, health checks, and rollback commands.

[Basic deployment](basic-deployment.md) · [Queue worker setup](queue-worker-setup.md)
