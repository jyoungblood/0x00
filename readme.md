"white-label" boilerplate for doing dashboard apps


inspo

"found myself in a place where my toolchain is pretty consistent again, decided to make a template repo so I can stop going through the same 50 CLI commands to spin this stuff up, here it is." - [https://github.com/xdesro/welcome-traveler](https://github.com/xdesro/welcome-traveler)


- [ ] diagram of all the pieces/architecture (codebases)
        - svelte fe
        - admin (back office) app
        - api app
    - note versions (stereo 1.4, dw 0.4, svelte + sveltekit + tailwind + whatever else)

- [ ] installation instructions (for every codebase, how to get working with MY stack [kh, whm/cpanel, bitbucket, vscode, etc])
    - instructions for commands to run to initialize new apps (and checklists for what to do to set up the sites)
    - set of commands to put it together from scratch (setup svelte, tailwind, etc)





++ components
- and then a few pages (oauth, login process, home page w/ nav, record list & detail, etc)


- [ ]  folders w/ canonical copy/paste stereo crud
    - readme links to folders of “white-lable” components/patterns (auth/login, etc)
    - document/collect "canonical" patterns for dw-based workflows
    - everything (that i’m using) from the dw “patterns” repos
    - [ ]  pattern for reading from svelte app (ajax api call)
        - [ ]  and saving data from dw-style forms
    - [ ]  dw/svelte pattern for authentication (general + social)
    - [ ]  dw/svelte pattern for handling uploads (single file, gallery batch)
    - [ ]  dw admin app w/ svelte (svelte FE & stereo api for BE)

- [ ]  future - related/future 0x00 specific use-case pre-assembled configs/patterns for ENTIRE SITES (auction sites, forums, stores, etc)