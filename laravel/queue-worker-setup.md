# queue worker setup

Add this with the first queued feature, such as photo processing. No worker is needed before the app dispatches queued jobs.
The starting plan uses MySQL and systemd. Redis is optional.

## App setup

- [ ] Implement queued jobs with `ShouldQueue`, retries, failure handling, and a visible processing state.
- [ ] Set `QUEUE_CONNECTION=database` in production `.env`.
- [ ] Check that migrations create `jobs`, `failed_jobs`, and any required `job_batches` table.
- [ ] Run `php artisan migrate --force` and `php artisan config:cache` as the account user.
- [ ] Choose the worker timeout for the actual jobs. Keep it shorter than the queue connection's `retry_after`.

The example uses a 60-second timeout and assumes `retry_after=90`.
Longer photo jobs need adjusted values. See [Laravel queues](https://laravel.com/docs/13.x/queues).

## Create the service once, as root

Replace `example` with the cPanel account. Replace the PHP path with the app's EasyApache CLI binary.
Save as `/etc/systemd/system/example-queue.service`:

```ini
[Unit]
Description=Laravel queue worker (example)
After=network.target

[Service]
Type=simple
User=example
Group=example
WorkingDirectory=/home/example/public_html
ExecStart=/opt/cpanel/ea-php83/root/usr/bin/php /home/example/public_html/artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=60
Restart=always
RestartSec=5
TimeoutStopSec=120

[Install]
WantedBy=multi-user.target
```

`Restart=always` also restarts the worker after Laravel requests a clean exit. [systemd docs](https://www.freedesktop.org/software/systemd/man/latest/systemd.service.html)
When you adjust the example, keep `TimeoutStopSec` longer than the longest job.

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now example-queue
sudo systemctl status example-queue --no-pager
```

Root manages the service, but PHP runs as `example`. cPanel reseller access alone does not grant systemd administration.

## Deploy and check

- [ ] Set `RESTART_QUEUE=1` in [deploy.sh](deploy.sh).
- [ ] Dispatch a test job and check that it completes.
- [ ] Run `php artisan queue:restart` as the account user and check that systemd starts a replacement worker.
- [ ] Check failed jobs with `php artisan queue:failed`.
- [ ] Inspect worker output with `sudo journalctl -u example-queue -n 50 --no-pager`.

Normal deployment needs no sudo. `queue:restart` lets the current job finish, then systemd starts the worker with updated code.
The deploy command and worker must use the same persistent cache for restart signals.

For [zero downtime deployment](zero-downtime-deployment.md), change the service paths to `app/current` and retain old releases until workers finish.
The Laravel scheduler is separate: scheduled tasks need their own cron setup.

[Basic deployment](basic-deployment.md)
