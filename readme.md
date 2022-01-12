![0x00 diagram](/images/0x00-diagram.png?raw=true)


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