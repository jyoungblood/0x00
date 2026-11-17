

### LARAVEL

- [Basic VPS Deployment (WHM)](laravel/basic-deployment.md)
- [zero downtime deployment (WHM)](laravel/zero-downtime-deployment.md)
- [queue worker setup](laravel/queue-worker-setup.md)
- [deploy.sh](laravel/deploy.sh)
- [monitor.sh](laravel/monitor.sh)
- [LARAVEL_GUIDELINES.MD](laravel/LARAVEL_GUIDELINES.MD)

### ASTRO / NODE
- [astro - ssg deploy process w/ cloudflare workers](https://github.com/jyoungblood/0x00/blob/master/deploy/astro-ssg-cloudflare.md)
- [dw  (astro ssr) deploy script for vps](https://github.com/jyoungblood/0x00/blob/master/deploy/deploy-astro-vps-ssh.sh)
  - [dw deploy docs](https://darkwavejs.com/docs/workflow/deployment/)
- [pm2 vps reseller systemd setup](https://github.com/jyoungblood/0x00/blob/master/pm2/pm2-vps.md)
  - [(old version, from dw deployment)](https://github.com/jyoungblood/0x00/blob/master/pm2/pm2-vps-old.md)




## DX CUSTOM

- [s3bk - mysql & static backsups to s3](https://github.com/jyoungblood/s3bk)

- [dbx - db export](https://github.com/jyoungblood/dbx) (formerly oceancom-db-export / oceancom-dump-local.sh)



### ARCHIVED DEPLOYMENT REFERENCE
- [generic deploy.sh (git push)](https://github.com/jyoungblood/0x00/blob/master/deploy/deploy.sh)
- [ssg deploy via ssh w/ rsync](https://github.com/jyoungblood/0x00/blob/master/deploy/deploy-ssg-ssh-rsync.sh)
- [(old) 0x00-laravel-deploy](https://github.com/jyoungblood/0x00-laravel-deploy)
- [0x00-11ty-deploy](https://github.com/jyoungblood/0x00-11ty-deploy)
- [0x00-astro-deploy](https://github.com/jyoungblood/0x00-astro-deploy)
- [0x00-stereo-deploy](https://github.com/jyoungblood/0x00-stereo-deploy) (works for slime too)




### LLM PRAYER

general ai project start prompt:

Please adhere to the guidelines defined in /Users/jy/Desktop/projects/hqn-laravel/LARAVEL_GUIDELINES.MD (move to the docs folder and reference this in the agents.md you'll create)

please assess the requirements thoroughly and create a roadmap and tracker. i'd love to execute this with an orchestrator pattern where one agent manages the process and subagents do the work (and the orchestrator will ask me any clarifying questions or alert me to any issues) 



PLEASE KEEP YOUR WRITING SIMPLE. i have adhd and i'm struggling with these damn llms writing so much bullshit for me to parse through. use as few words as you can and prefer outline format and bullet points over paragraphs. please and thank you :)