This guide assumes Arch Linux. Although exact instructions for other
distributions are different, the steps stay roughly the same.

#### Installing hard dependencies

```console
user@host:~$ sudo pacman -S postgres
user@host:~$ sudo pacman -S python
user@host:~$ sudo pacman -S python-pip
user@host:~$ sudo pacman -S npm
user@host:~$ python --version
Python 3.5.1
```



#### Setting up a database

First, basic `postgres` configuration:

```console
user@host:~$ sudo -i -u postgres initdb --locale en_US.UTF-8 -E UTF8 -D /var/lib/postgres/data
user@host:~$ sudo systemctl start postgresql
user@host:~$ sudo systemctl enable postgresql
```

Then creating a database:

```console
user@host:~$ sudo -i -u postgres createuser --interactive
Enter name of role to add: szuru
Shall the new role be a superuser? (y/n) n
Shall the new role be allowed to create databases? (y/n) n
Shall the new role be allowed to create more new roles? (y/n) n
user@host:~$ sudo -i -u postgres createdb szuru
user@host:~$ sudo -i -u postgres psql -c "ALTER USER szuru PASSWORD 'dog';"
```



#### Installing soft dependencies

```console
user@host:path/to/szurubooru$ sudo pip install -r requirements.txt # installs backend deps
user@host:path/to/szurubooru$ npm install # installs frontend deps
```



#### Preparing `szurubooru` for first run

Configure things:

```console
user@host:path/to/szurubooru$ cp config.ini.dist config.ini
user@host:path/to/szurubooru$ vim config.ini
```

Pay extra attention to the `[database]` and `[smtp]` sections, and API URL in
`[basic]`.

Then update the database and compile the frontend:

```console
user@host:path/to/szurubooru$ alembic update head # runs all DB upgrades
user@host:path/to/szurubooru$ npm run build # compiles frontend
```

`alembic` should have been installed during installation of `szurubooru`'s
dependencies.

It is recommended to rebuild the frontend after each change to configuration.



### Wiring `szurubooru` to the web server

`szurubooru` is divided into two parts: public static files, and the API. It
tries not to impose any networking configurations on the user, so it is the
user's responsibility to wire these to their web server.

Below are described the methods to integrate the API into a web server:

1. Run API locally with `waitress`, and bind it with a reverse proxy. In this
   approach, the user needs to install `waitress` with `pip install waitress`
   and then start `szurubooru` with `./scripts/host-waitress` (see `--help` for
   details). Then the user needs to add a virtual host that delegates the API
   requests to the local API server, and the browser requests to the `public/`
   directory.
2. Alternatively, Apache users can use `mod_wsgi`.
3. Alternatively, users can use other WSGI frontends such as `gunicorn` or
   `uwsgi`, but they'll need to write wrapper scripts themselves.

Note that the API URL in the virtual host configuration needs to be the same as
the one in the `config.ini`, so that client knows how to access the backend!

#### Example

**nginx configuration** - wiring API `http://great.dude/api/` to
`localhost:6666` to avoid fiddling with CORS:

```nginx
server {
    listen 80;
    server_name great.dude;

    location = /api {
        return 302 /api/;
    }
    location ~ ^/api(/?)(.*)$ {
        proxy_pass http://127.0.0.1:6666/$2$is_args$args;
    }
    location / {
        root /home/rr-/src/maintained/szurubooru/public;
        try_files $uri /index.htm;
    }
}
```

**`config.ini`**:

```ini
[basic]
api_url = http://big.dude/api/
```

Then the backend is started with `./scripts/host-waitress`.
