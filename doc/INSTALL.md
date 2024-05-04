This assumes that you have Docker (version 17.05 or greater)
and Docker Compose (version 1.6.0 or greater) already installed.

### Prepare things

1. Download the `szurubooru` source:

    ```console
    user@host:~$ git clone https://github.com/rr-/szurubooru.git szuru
    user@host:~$ cd szuru
    ```
2. Configure the application:

    ```console
    user@host:szuru$ cp server/config.yaml.dist server/config.yaml
    user@host:szuru$ edit server/config.yaml
    ```

    Pay extra attention to these fields:

    - secret
    - the `smtp` section.

    You can omit lines when you want to use the defaults of that field.

3. Configure Docker Compose:

    ```console
    user@host:szuru$ cp doc/example.env .env
    user@host:szuru$ edit .env
    ```

    Change the values of the variables in `.env` as needed.
    Read the comments to guide you. Note that `.env` should be in the root
    directory of this repository.

### Running the Application

Download containers:
```console
user@host:szuru$ docker-compose pull
```

For first run, it is recommended to start the database separately:
```console
user@host:szuru$ docker-compose up -d sql
```

To start all containers:
```console
user@host:szuru$ docker-compose up -d
```

To view/monitor the application logs:
```console
user@host:szuru$ docker-compose logs -f
# (CTRL+C to exit)
```

To stop all containers:
```console
user@host:szuru$ docker-compose down
```

### Additional Features

1. **CLI-level administrative tools**

    You can use the included `szuru-admin` script to perform various
    administrative tasks such as changing or resetting a user password. To
    run from docker:

    ```console
    user@host:szuru$ docker-compose run server ./szuru-admin --help
    ```

    will give you a breakdown on all available commands.

2. **Using a seperate domain to host static files (image content)**

    If you want to host your website on, (`http://example.com/`) but want
    to serve the images on a different domain, (`http://static.example.com/`)
    then you can run the backend container with an additional environment
    variable `DATA_URL=http://static.example.com/`. Make sure that this
    additional host has access contents to the `/data` volume mounted in the
    backend.

3. **Setting a specific base URI for proxying**

    Some users may wish to access the service at a different base URI, such
    as `http://example.com/szuru/`, commonly when sharing multiple HTTP
    services on one domain using a reverse proxy. In this case, simply set
    `BASE_URL="/szuru/"` in your `.env` file.

    Note that this will require a reverse proxy to function. You should set
    your reverse proxy to proxy `http(s)://example.com/szuru` to
    `http://<internal IP or hostname of frontend container>/`. For an NGINX
    reverse proxy, that will appear as:

    ```nginx
    location /szuru {
        proxy_http_version 1.1;
        proxy_pass http://<internal IP or hostname of frontend container>/;

        proxy_set_header Host              $http_host;
        proxy_set_header Upgrade           $http_upgrade;
        proxy_set_header Connection        "upgrade";
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Scheme          $scheme;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Script-Name     /szuru;
    }
    ```

4. **Preparing for production**

    If you plan on using szurubooru in a production setting, you may opt to
    use a reverse proxy for added security and caching capabilities. Start
    by having the client docker listen only on localhost by changing `PORT`
    in your `.env` file to `127.0.0.1:8080` instead of simply `:8080`. Then
    configure NGINX (or your caching/reverse proxy server of your choice)
    to proxy_pass `http://127.0.0.1:8080`. An example config is shown below:

    ```nginx
    # ideally, use ssl termination + cdn with a provider such as cloudflare.
    # modify as needed!

    # rate limiting zone
    # poor man's ddos protection, essentially
    limit_req_zone $binary_remote_addr zone=throttle:10m rate=25r/s;

    # www -> non-www
    server {
      listen 80;
      listen [::]:80;
      server_tokens off;
      server_name www.example.com
      return 301 http://example.com$request_uri;
    }

    server {
      server_name example.com;
      client_max_body_size 100M;
      client_body_timeout 30s;
      server_tokens off;
      location / {
        limit_req zone=throttle burst=5 delay=3;
        proxy_http_version 1.1;
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $http_host;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Scheme $scheme;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Script-Name /szuru;
        error_page 500 501 502 504 505 506 507 508 509 510 511 @err;
        error_page 503 @throttle;
      }

      location @err {
        return 500 "server error. please try again later.";
        default_type text/plain;
      }
      location @throttle {
        return 503 "we've detected abuse on your ip. please wait and try again later.";
        default_type text/plain;
      }
      listen 80;
      listen [::]:80;
    }
    ```
