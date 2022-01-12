# 0x00 - Full CRUD app starter kit

[setup guide](#setup-guide)  | [workflow](#workflow) | [boilerplate](/boilerplate/readme.md) | [component catalog](/components/readme.md)

![0x00 diagram](/images/0x00-diagram.png?raw=true)



@eqkh
- [ ] add domains & get ftp creds
- [ ] init db & get creds







## Setup Guide


# Svelte

### sveltekit install
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