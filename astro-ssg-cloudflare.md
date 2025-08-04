

# JYM site init recipe
## Create new Astro site (SSG) & deploy w/ CF workers/pages

- [ ] register domain (cloudflare, maybe porkbun)
  - add domain to cloudflare, change NS if needed

- [ ] @github (pages) or @bitbucket - create new repo

- [ ] @mbp
  - `npm create astro@latest`
    - `npx astro add tailwind`
  - `git init`
    - (add repo & push to main)
  - add analytics tag (beam, see any other JYM site [ex, antipatterns])
  - add to "sites" sheet (numbers)
  - add [generic deploy.sh (git push)](https://github.com/jyoungblood/0x00/blob/master/deploy.sh) script

- [ ] @cloudflare
  - "compute (workers)"
    - "workers & pages"
      - (workers) "create"
        - "import repository"
          - deploy w/ astro preset
      - (pages) "connect to git"
  - ?? connect app/worker to domain (cname record)
    - "workers & pages" --> "custom domain"

(future: want to do this w/ [cloudflare cli](https://developers.cloudflare.com/workers/get-started/guide/) instead)



---

### REF
- [CF Pages](https://developers.cloudflare.com/pages/)
- [CF Workers](https://developers.cloudflare.com/workers/)
