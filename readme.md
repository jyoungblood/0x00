<h1 align="center">0x00 - Full CRUD app starter kit</h1>

<p align="center">
[setup guide](#setup-guide)  | [workflow](#workflow) | [boilerplate](/boilerplate/readme.md) | [component catalog](/components/readme.md)
</p>

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


### 3 - (FE) [sveltekit](https://kit.svelte.dev/) install
@local

- npm init svelte@next my-app
- cd my-app
- npm install
- npm run dev

@server

- clone repo (git init, remote add origin, fetch --all, pull origin master)
- change docroot to /build
- add .htaccess to /build

sveltekit build

- install static adapter - [https://github.com/sveltejs/kit/tree/master/packages/adapter-static](https://github.com/sveltejs/kit/tree/master/packages/adapter-static)



### 4 - (api) [stereo](https://stereotk.com/) install


### 5 - (admin) [darkwave](https://darkwave.ltd/) install





---



## Workflow



### svelte dev workflow

- npm run dev

### svelte deploy workflow

@local

- npm run build
- git commit
- git push

@server

- cd dir
- git pull


