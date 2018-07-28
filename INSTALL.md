This assumes that you have Docker (version 17.05 or greater)
and Docker Compose (version 1.6.0 or greater) already installed.

### Prepare things

1. Getting `szurubooru`:

    ```console
    user@host:~$ git clone https://github.com/rr-/szurubooru.git szuru
    user@host:~$ cd szuru
    ```
2. Configure the application:

    ```console
    user@host:szuru$ cp server/config.yaml.dist config.yaml
    user@host:szuru$ edit config.yaml
    ```

    Pay extra attention to these fields:

    - secret
    - the `smtp` section.

    You can omit lines when you want to use the defaults of that field.

3. Configure Docker Compose:

    ```console
    user@host:szuru$ cp docker-compose.yml.example docker-compose.yml
    user@host:szuru$ edit docker-compose.yml
    ```

    Read the comments to guide you. For production use, it is *important*
    that you configure the volumes appropriately to avoid data loss.

### Running the Application

1. Configurations for ElasticSearch:

    You may need to raise the `vm.max_map_count`
    parameter to at least `262144` in order for the
    ElasticSearch container to function. Instructions
    on how to do so are provided
    [here](https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html#docker-cli-run-prod-mode).

2. Build or update the containers:

    ```console
    user@host:szuru$ docker-compose pull
    user@host:szuru$ docker-compose build --pull
    ```

    This will build both the frontend and backend containers, and may take
    some time.

3. Start and stop the the application

    ```console
    # To start:
    user@host:szuru$ docker-compose up -d
    # To monitor (CTRL+C to exit):
    user@host:szuru$ docker-compose logs -f
    # To stop
    user@host:szuru$ docker-compose down
    ```
