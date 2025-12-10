these are abstractions (and manager scripts) for doing backups to s3/r2 using s3cmd
  so get that set up first
  [link] here's how

s3cmd ref - https://s3tools.org/usage


then put these scripts on your machine
do whatever config
and set a cron job to run it at whatever intervals



- [s3bk-db](https://github.com/jyoungblood/0x00/blob/master/backup/s3bk-db.sh)

- [s3bk-static](https://github.com/jyoungblood/0x00/blob/master/backup/s3bk-static.sh)




---

based on s3mysqlbackup.sh - https://gist.github.com/oodavid/2206527/


---













### Mysql backup script

\* do automated backups with cpanel instead

- [https://storageswiss.com/2017/04/13/how-to-backup-a-cpanel-website-to-amazon-s3/](https://storageswiss.com/2017/04/13/how-to-backup-a-cpanel-website-to-amazon-s3/)
- [https://storageswiss.com/2017/04/07/how-to-backup-a-cpanel-website/](https://storageswiss.com/2017/04/07/how-to-backup-a-cpanel-website/)

### Install s3cmd

@skylab (Ubuntu 16.04)

```
apt-get install s3cmd

```

@kh (CentOS 7 / WHM)

```
yum install s3cmd

```

### Configure with s3 credentials

```
s3cmd --configure

```

### (if needed) move the creds to the home folder of the account that will be running it

```html
mv /root/.s3cfg /home/equinox
chown equinox:equinox /home/equinox/.s3cfg
```

### Make the backup script

for kh vps, should be the home folder of the reseller account ([purhost.net](http://purhost.net) (/home/purhost), [equinoxhosting.com](http://equinoxhosting.com) (/home/equinox), [reininghost.com](http://reininghost.com) (/home/reininghost), etc)

```
touch /home/equinox/s3mysqlbackup.sh
nano /home/equinox/s3mysqlbackup.sh

```

// copy [the contents of this gist](https://gist.github.com/oodavid/2206527) with your Mysql password & s3 credentials

- also need to exclude any databases that don’t need to be backed up (ex: |reininghost_rsdb\)
- one time i had to set password to `mysqlpassword="$(cat /etc/psa/.psa.shadow)"`​ but it worked wtf

  - lol i got it to work, see /root/s3mysqlbackup.sh on nrhahost 🤠

### Make it executable

```
chown equinox:equinox /home/equinox/s3mysqlbackup.sh
chmod +x /home/equinox/s3mysqlbackup.sh

```

### Add to cron (run every morning at 3am)

cpanel add cron job to reseller root acct (where i keep all the cron jobs)

```html
0 3 * * * bash ~/s3mysqlbackup.sh
```

OLD VERSION

```
crontab -e

```

```
0 3 * * * bash /sites/.s3mysqlbackup.sh >/dev/null 2>&1

```

‍

OLD versions of centos/rhel6 - add s3cmd to available packages before yum install - [https://gist.github.com/abhinavmsra/765f3c1aaa9a75d19c72](https://gist.github.com/abhinavmsra/765f3c1aaa9a75d19c72)











---
---

???



batch transfer to/from s3 using s3cmd

- potential solution here - [https://superuser.com/questions/453568/s3cmd-how-to-copy-files-from-local-to-s3-bucket-with-existing-structure](https://superuser.com/questions/453568/s3cmd-how-to-copy-files-from-local-to-s3-bucket-with-existing-structure)
- s3cmd docs - [https://s3tools.org/s3cmd](https://s3tools.org/s3cmd)