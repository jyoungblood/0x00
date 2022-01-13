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
npx degit darkwve
vscode sftp (creds + upload)
git init & push
?? add vscode to gitignore?
?? anything on the server? (set up repo?)

go through setup process w/ dw (connect to db)




### 4 - (api) [stereo](https://stereotk.com/) install

@local
npx degit stereo
vscode sftp (creds + upload)
git init & push (on server? & overwrite local copy when done?)
?? add vscode to gitignore?
?? anything on the server? (set up repo?)

edit settings to connect to database
copy/paste a simple test route





### 5 - (FE) [sveltekit](https://kit.svelte.dev/) install
@local

- npm init svelte@next my-app
- cd my-app
- npm install
- npm run dev

- install static adapter - [https://github.com/sveltejs/kit/tree/master/packages/adapter-static](https://github.com/sveltejs/kit/tree/master/packages/adapter-static)

- git init & push

?? copy/paste simple route to connect to api & verify working



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
- end of sesh
  - @ server: cd /s/blu-api, git add/commit/push
  - @ local: git fetch --all, git reset --hard origin/master




# example

## dropdown

<details>
<summary>To make sure markdown is rendered correctly in the collapsed section...</summary>

 1. Put an **empty line** after the `<summary>` block.
 2. *Insert your markdown syntax*
 3. Put an **empty line** before the `</details>` tag
 
</details>

## file tree

```graphql
# Code & components for pages
./src/* 
  ├─ src/assets - # Minified images, fonts, icon files
  ├─ src/components - # Individual smaller components
  ├─ src/fragments - # Larger chunks of a page composed of multiple components
  ├─ src/layouts - # Page layouts used for different types of pages composed of components and fragments
  ├─ src/page - # Custom pages or pages composed of layouts with hardcoded data components, fragments, & layouts
  ├─ src/pages/* - # Next.js file based routing
  │  ├─ _app.js - # next.js app entry point
  │  ├─ _document.js - # next.js document wrapper
  │  ├─ global.css - #  Global CSS styles
  │  └─ Everything else... - # File based routing
  └─ src/utils - # Utility functions used in various places
```