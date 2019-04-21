**This installation guide is deprecated and might be out
of date! It is recommended that you deploy using
[Docker](https://github.com/rr-/szurubooru/blob/master/INSTALL.md)
instead.**

This guide assumes Arch Linux. Although exact instructions for other
distributions are different, the steps stay roughly the same.

### Installing hard dependencies

Szurubooru requires the following dependencies:
- Python (3.5 or later)
- Postgres
- FFmpeg
- node.js

```console
user@host:~$ sudo pacman -S postgresql
user@host:~$ sudo pacman -S python
user@host:~$ sudo pacman -S python-pip
user@host:~$ sudo pacman -S ffmpeg
user@host:~$ sudo pacman -S npm
user@host:~$ sudo pacman -S elasticsearch
user@host:~$ sudo pip install virtualenv
user@host:~$ python --version
Python 3.5.1
```

The reason `ffmpeg` is used over, say, `ImageMagick` or even `PIL` is because of
Flash and video posts.



### Setting up a database

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



### Setting up elasticsearch

```console
user@host:~$ sudo systemctl start elasticsearch
user@host:~$ sudo systemctl enable elasticsearch
```

### Preparing environment

Getting `szurubooru`:

```console
user@host:~$ git clone https://github.com/rr-/szurubooru.git szuru
user@host:~$ cd szuru
```

Installing frontend dependencies:

```console
user@host:szuru$ cd client
user@host:szuru/client$ npm install
```

`npm` sandboxes dependencies by default, i.e. installs them to
`./node_modules`. This is good, because it avoids polluting the system with the
project's dependencies. To make Python work the same way, we'll use
`virtualenv`. Installing backend dependencies with `virtualenv` looks like
this:

```console
user@host:szuru/client$ cd ../server
user@host:szuru/server$ virtualenv python_modules # consistent with node_modules
user@host:szuru/server$ source python_modules/bin/activate # enters the sandbox
(python_modules) user@host:szuru/server$ pip install -r requirements.txt # installs the dependencies
```



### Preparing `szurubooru` for first run

1. Compile the frontend:

    ```console
    user@host:szuru$ cd client
    user@host:szuru/client$ node build.js
    ```

    You can include the flags `--no-transpile` to disable the JavaScript
    transpiler, which provides compatibility with older browsers, and
    `--debug` to generate JS source mappings.

2. Configure things:

    ```console
    user@host:szuru/client$ cd ..
    user@host:szuru$ mv server/config.yaml.dist .
    user@host:szuru$ cp config.yaml.dist config.yaml
    user@host:szuru$ vim config.yaml
    ```

    Pay extra attention to these fields:

    - data directory,
    - data URL,
    - database,
    - the `smtp` section.

3. Upgrade the database:

    ```console
    user@host:szuru/client$ cd ../server
    user@host:szuru/server$ source python_modules/bin/activate
    (python_modules) user@host:szuru/server$ alembic upgrade head
    ```

    `alembic` should have been installed during installation of `szurubooru`'s
    dependencies.

4. Run the tests:

    ```console
    (python_modules) user@host:szuru/server$ pytest
    ```

It is recommended to rebuild the frontend after each change to configuration.



### Wiring `szurubooru` to the web server

`szurubooru` is divided into two parts: public static files, and the API. It
tries not to impose any networking configurations on the user, so it is the
user's responsibility to wire these to their web server.

The static files are located in the `client/public/data` directory and are
meant to be exposed directly to the end users.

The API should be exposed using WSGI server such as `waitress`, `gunicorn` or
similar. Other configurations might be possible but I didn't pursue them.

API calls are made to the relative URL `/api/`. Your HTTP server should be
configured to proxy this URL format to the WSGI server. Some users may prefer
to use a dedicated reverse proxy for this, to incorporate additional features
such as load balancing and SSL.

Note that the API URL in the virtual host configuration needs to be the same as
the one in the `config.yaml`, so that client knows how to access the backend!

#### Example

In this example:

- The booru is accessed from `http://example.com/`
- The API is accessed from `http://example.com/api`
- The API server listens locally on port 6666, and is proxied by nginx
- The static files are served from `/srv/www/booru/client/public/data`

**nginx configuration**:

```nginx
server {
    listen 80;
    server_name example.com;

    location ~ ^/api$ {
        return 302 /api/;
    }
    location ~ ^/api/(.*)$ {
        if ($request_uri ~* "/api/(.*)") { # preserve PATH_INFO as-is
            proxy_pass http://127.0.0.1:6666/$1;
        }
    }
    location / {
        root /srv/www/booru/client/public;
        try_files $uri /index.htm;
    }
}
```

**`config.yaml`**:

```yaml
data_url: 'http://example.com/data/'
data_dir: '/srv/www/booru/client/public/data'
```

To run the server using `waitress`:

```console
user@host:szuru/server$ source python_modules/bin/activate
(python_modules) user@host:szuru/server$ pip install waitress
(python_modules) user@host:szuru/server$ waitress-serve --port 6666 szurubooru.facade:app
```

or `gunicorn`:

```console
user@host:szuru/server$ source python_modules/bin/activate
(python_modules) user@host:szuru/server$ pip install gunicorn
(python_modules) user@host:szuru/server$ gunicorn szurubooru.facade:app -b 127.0.0.1:6666
```
