This is rewrite of [`szurubooru` 0.9.x](https://github.com/rr-/szurubooru) that
intends to:

- Improve user experience: better upload form, larger thumbnails, make top
  navigation stay out of user way. Maybe other goodies!
- Finally define sane REST API without unnecessary blobs and with proper
  documentation.
- Simplify registration - user registers, and they're able to post. (You'll
  still be able to make it kind of invite-only via default permissions.)
- Replace PHP with Python 3.5.
- Replace prior JS mess with proper MVC.
- Replace MySQL (MariaDB) with Postgres.
- Replace `composer`, `npm`, `mod_rewrite` (=Apache), `imagick`, `pdo_mysql`
  with just `pip` and `npm` (+ sandboxed dependencies).
- Replace `grunt` with `npm` scripts.
- Make hosting more flexible: offer simple self hosted app that can be combined
  with any reverse proxy.
- Reduce codebase size - the original szurubooru was at 30KSLOC (`git
  line-summary`), let's see how much this can be brought down.
