
# HXSF site init
Start a new static site using [scratch](https://github.com/hxgf/scratch), to be deployed on eqkh (either as a subdomain of hxgf.io or with its own domain). 

For example: [jy.hxgf.io](https://jy.hxgf.io/)

### @kh (eqkh)
- [ ] create new domain/subdomain
- [ ] create new ftp user *(if needed)*

### @github
- [ ] [create a new repo](https://github.com/new)


### @local (~/projects)
- [ ] initialize site
```
mkdir jy.hxgf
cd jy.hxgf
npx degit hxgf/scratch
```
- [ ] add ftp creds for vscode (w/ [sftp sync extension](https://marketplace.visualstudio.com/items?itemName=liximomo.sftp))
- a) in vscode: `cmd+shift+p` --> 'SFTP: Config'
- b) or copy config from hxgf
```
mkdir .vscode && cp ../hxgf.io/.vscode/sftp.json ./.vscode
```

- [ ] upload everything so far

- [ ] add .gitignore
```
echo ".DS_Store\n.vscode/" >> .gitignore
```
- [ ] add hxgf bug
```
cp ../hxgf.io/images/hxgf-bug.svg ./images
```
- [ ] link to source on gh (in footer w/ copyright notice)
```
  <div class="tc pv3">
    <a target="_blank" href="https://hxgf.io"><img class="w3 dib" src="./images/hxgf-bug.svg" /></a><br />
    <a target="_blank" class="f6 black" href="https://github.com/hxgf/jy.hxgf.io">src</a>
  </div>
```
- [ ] add gh-friendly readme
```
cp ../hxgf.io/readme.md .
```
- [ ] add new repos to codebases & push it
```
git init
git remote add origin 'git@github.com:hxgf/jy.hxgf.io.git'
git add --all
git commit -am "ex nihilo"
git push -u origin master
```
