#!/bin/bash

# This script is meant to automate the deployment of Szurubooru via Docker/Docker-Compose,
# and to automatically take the necessary steps to allow Szurubooru to run on ARM devices
# such as the Raspberry Pi.

###########################################[Notes]###########################################
# * Written and tested on Raspberry Pi 4 running Raspbian 10 (armv7), heavily based on a fork
#   by kaijuagenda for the Pi 3: https://github.com/kaijuagenda/szurubooru-rpi3
#
# * Modifies docker-compose.yml rather than relying on a static alternative file to hopefully
#   avoid the need for parallel maintenance of two files.
#############################################################################################

echo "Welcome to Szurubooru.  This install script will ask you to set a few config values, then get szurubooru up and running."

# Check to ensure Docker and Docker-Compose are both installed before proceeding
if ! command -v docker &> /dev/null || ! command -v docker-compose &> /dev/null; then
    echo "Both Docker and Docker-Compose must already be installed to use this script."
    echo "Please ensure both are installed and accessible using commands 'docker' and 'docker-compose', then run this script again."
    exit 1
else echo "Docker and Docker-Compose verified.  Continuing installation..."
fi

########################################################################################
# Check CPU architectue and, if ARM (such as Raspberry Pi), modify docker-compose.yml
# https://github.com/rr-/szurubooru/wiki/ARM-and-Raspberry-Pi-Support-for-Docker-builds
# https://github.com/rr-/szurubooru/blob/master/docker-compose.yml
########################################################################################

if [[ "$(uname -m)" == 'a'* ]]; then
    echo "ARM architecture detected.  Modfying docker-compose.yml accordingly."
    sed -zi "s|image: szurubooru/server:latest|build:\n      context: ./server|; \     # Modify services/server to build locally instead of pulling from DockerHub
             s|image: szurubooru/client:latest|build:\n      context: ./client|; \     # Modify services/client to build locally instead of pulling from DockerHub
             s|depends_on:\n      - sql|depends_on:\n      - sql\n      - elasticsearch|; \ # Add dependency for alternate arm compatible elasticsearch defined below.
             s|POSTGRES_HOST: sql|POSTGRES_HOST: sql\n      ESEARCH_HOST: elasticsearch'|" docker-compose.yml # Point Postgres to the alternate elasticsearch container

    # Add a new section to define the alternate elasticsearch service
    cat >> docker-compose.yml <<-ESS
	  elasticsearch:
	    image: ind3x/rpi-elasticsearch #recommended in the wiki, does get the job done.
	    environment:
	      ## Specifies the Java heap size used
	      ## Read
	      ##  https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html
	      ## for more info
	      ES_JAVA_OPTS: -Xms512m -Xmx512m
	    volumes:
	      - index:/usr/share/elasticsearch/data
	volumes:
	  index: # Scratch space for ElasticSearch index, will be rebuilt if lost
	ESS
fi


function server_config () {
    ################################################################################################################
    # Copy ./server/config.yaml.dist to ./server/config.yaml, then prompt the user to set the basic config settings
    # https://github.com/rr-/szurubooru/blob/master/doc/INSTALL.md
    ################################################################################################################

    cp server/config.yaml.dist server/config.yaml

    echo "===[General Settings]==="

    # Prompt for Secret, proposing a randomly generated 32-character alphanumeric value as a default.
    default_secret="$(tr -dc '[:alnum:]' < /dev/urandom | dd bs=4 count=8 2>/dev/null)"
    echo "Enter your Secret (Used to salt the users' password hashes and generate filenames for static content)"; read -e -p "> " -i "$default_secret" SECRET
    sed -i "s|secret: change|secret: $SECRET|" server/config.yaml

    # Other useful (but less important) settings
    echo "Enter the desired name for your server. (Shown in the website title and on the front page)"; read -e -p "> " -i "szurubooru" SERVERNAME
    echo "Enter the full url to the homepage of this szurubooru site, with no trailing slash."; read -e -p "> " URL
    sed -i "s|name: szurubooru|name: $SERVERNAME|;s|domain: |domain: $URL|" server/config.yaml

    # SMTP (email) settings
    echo "===[SMTP (Email) Settings]==="
    echo "If host name is left blank, the password reset feature will be disabled."
    echo "Enter your email server's host address."
    read -e -p "> " SMTP_HOST

    if [ -n "$SMTP_HOST" ]; then # Prompt for additional SMTP details and then update server/config.yaml if host was set
        echo "Enter the Port number for your SMTP server"; read -e -p "> " SMTP_PORT
        echo "Enter the UserName for your SMTP server"; read -e -p "> " SMTP_USER
        echo "Enter the Password for your SMTP server"; read -e -p -s "> " SMTP_PASS
        echo "Enter the 'From' address emails should show"; read -e -p "> " SMTP_FROM
        sed -i "s|host: |host: $SMTP_HOST|; \
                s|port: |port: $SMTP_PORT|; \
                s|user: |user: $SMTP_USER|; \
                s|pass: |pass: $SMTP_PASS|; \
                s|from: |from: $SMTP_FROM|" \
                server/config.yaml
    else # Warn user that they should set a contact email if no smtp host is specified
        echo "WARNING:  No SMTP host specified!"
        echo "It is recommended you set a contact email in the next prompt for manual password reset requests."
    fi

    echo "Enter your server's primary contact email address."
    read -e -p "> " CONTACT_ADDR
    if [ -n "$CONTACT_ADDR" ]; then sed -i "s|contact_email: |contact_email: $CONTACT_ADDR|"; fi
}

function set_env () {
    ############################################################################################################
    # Copy ./doc/example.env to ./.env, then prompt the user to set the basic config settings
    # https://github.com/rr-/szurubooru/blob/master/doc/INSTALL.md
    ############################################################################################################
    cp doc/example.env .env
    echo "===[Environmental Variables]==="
    echo "Enter your desired database username"; read -e -p "> " -i "szuru" DB_USER
    while true; do
        echo "Enter your desired database password"; read -e -p -s "> " DB_PASS
        if [ -n $DB_PASS ]; break
            else echo "ERROR: You must set a password!"
        fi
    done
    echo "Enter the build info you'd like to display on the home screen"; read -e -p "> " -i "latest" BUILD_INFO
    echo "Enter the port # to expose the HTTP service to. "
    echo "Set to 127.0.0.1:8080 if you wish to reverse proxy the docker's port."; read -e -p "> " -i "8080" PORT
    echo "Enter the URL base to run szurubooru under"; read -e -p "> " -i "/" BASE_URL
    echo "Enter the directory you wish to store image data in"; read -e -p "> " -i "/var/local/szurubooru/data" MOUNT_DATA
    echo "Enter the directory in which you wish to store database files"; read -e -p "> " -i "/var/local/szurubooru/sql" MOUNT_SQL
    sed -i "s|POSTGRES_USER=szuru|POSTGRES_USER=$DB_USER|; \
            s|POSTGRES_PASSWORD=changeme| POSTGRES_PASSWORD=$DB_PASS|; \
            s|BUILD_INFO=latest|BUILD_INFO=$BUILD_INFO|; \
            s|PORT=8080|PORT=$PORT|; \
            s|BASE_URL=/|BASE_URL=$URL|; \
            s|MOUNT_DATA=/var/local/szurubooru/data|MOUNT_DATA=$MOUNT_DATA|; \
            s|MOUNT_SQL=/var/local/szurubooru/sql|MOUNT_SQL=$MOUNT_SQL|" .env
}

server_config # Configuration via ./server/config.yaml
set_env # Configuration via ./.env
mount=$(grep "MOUNT_DATA" .env | sed 's/MOUNT_DATA=//'); chown 1000:1000 "$mount" # Ensures users can upload files
docker-compose pull # Download containers
docker-compose up -d sql # Start SQL first
sleep 30 # Give the database time to become available
docker-compose up -d # Start remaining containers
