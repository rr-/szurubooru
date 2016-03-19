This is rewrite of `szurubooru` 0.9.x that intends to

- Improve user experience within frontend. No more vertical user list. Better
  upload form, larger thumbnails, make top navigation stay out of user way.
  Maybe other goodies!
- Finally define sane REST API (with no bullshit such as SQL queries, request
  timings or exception stack traces this time)
- Simplify registration - user registers, and they're able to post. No
  activation e-mails, no nothing (email's going to be used **ONLY** for
  password reminders, yes, *not even* for confirmation). Note that you will
  have control over permissions, user ranks and the default user rank, so you
  might be able to setup a system where user needs to be approved by mod to
  join the community.
- Maybe simplify permission system
- Ditch PHP in favor of something more serious (python 3.5)
- Ditch in-house JS monstrosities in favor of something more serious (I've got
  EmberJS on my radar)
- Replace dependencies such as composer, npm, grunt, and all that crap with
  just python, and a few pip packages
- Simplify hosting: offer simple self hosted app combinable with reverse proxies
- Replace MySQL (/ MariaDB) with Postgres
- Less god damn code! 24KSLOC? For a thing this simple? The goal is to fit
  within 15KSLOC. Let's see if I can accomplish this.
