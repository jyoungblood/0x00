# 0x00 - Full CRUD app starter kit

| [Setup Guide](#setup-guide)  | [Workflow](#workflow) | [Boilerplate](/boilerplate/readme.md) | [Component Catalog](/components/readme.md) |

![0x00 diagram](/images/0x00-diagram.png?raw=true)


---

## Setup Guide


### 1 - env setup (@eqkh)

- [ ] add domains & get ftp creds
  - [ ] projectdomain.com
  - [ ] api.projectdomain.com
  - [ ] admin.projectdomain.com

- [ ] init db & get creds



### 2 - create repos

- [ ] [create new repos](https://bitbucket.org/repo/create) @ bitbucket
  - [ ] projectdomain.com
  - [ ] api.projectdomain.com
  - [ ] admin.projectdomain.com







### 3 - (admin) [darkwave](https://darkwave.ltd/) install
@local
- npx degit darkwve
- vscode sftp (creds + upload)
- git init & push
- ?? add vscode to gitignore?
- ?? anything on the server? (set up repo? or just do git stuff locally?)

- go through setup process w/ dw (connect to db)




### 4 - (api) [stereo](https://stereotk.com/) install

@local
- npx degit stereo
- vscode sftp (creds + upload)
- git init & push (on server? & overwrite local copy when done?)
- ?? add vscode to gitignore?
- ?? anything on the server? (set up repo? or just do git stuff locally?)

- edit settings to connect to database
- copy/paste a simple test route





### 5 - (FE) [sveltekit](https://kit.svelte.dev/) install
@local

- npm init svelte@next my-app
- cd my-app
- npm install
- npm run dev

- install static adapter - [https://github.com/sveltejs/kit/tree/master/packages/adapter-static](https://github.com/sveltejs/kit/tree/master/packages/adapter-static)

- git init & push

- ?? copy/paste simple route to connect to api & verify working

- ?? special stuff we have to do to set up tailwind?


@server

- clone repo (git init, remote add origin, fetch --all, pull origin master)
- change docroot to /build
- add .htaccess to /build









---



## Workflow



### (FE) svelte dev/deploy workflow

@local

- npm run dev
- npm run build
- git commit
- git push

@server

- cd dir
- git pull


### (api/admin) stereo/dw dev workflow
- ?? edit the code in vscode, automatically uploaded via ftp
- ?? end of sesh
  - @ server: cd /s/blu-api, git add/commit/push
  - @ local: git fetch --all, git reset --hard origin/master
- OR - just use git locally, version on the server is just sent via ftp


