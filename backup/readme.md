these are abstractions (and manager scripts) for doing backups to s3/r2 using s3cmd
  so get that set up first
  [link] here's how

s3cmd ref - https://s3tools.org/usage


then put these scripts on your machine
do whatever config
and set a cron job to run it at whatever intervals



- [s3bk-db](https://github.com/jyoungblood/0x00/blob/master/backup/s3bk-db.sh)

- [s3bk-static](https://github.com/jyoungblood/0x00/blob/master/backup/s3bk-static.sh)