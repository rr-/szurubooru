#!/bin/bash

# NOTE: This script is still a work in progress

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

# Colorized flags for check status output
ERROR="[\e[31mERROR\e[0m]"
OK="[\e[32mOK\e[0m]"
NOTICE="[\e[33mNOTICE\e[0m]"

echo 'Welcome to Szurubooru.  This install script will ask you to set a few basic'\
     'config values, then get szurubooru up and running.'

echo "Checking a few things to ensure successful deployment..."

# Check to ensure Docker and Docker-Compose are both installed and usable via standard commands
if ! command -v docker &> /dev/null || ! command -v docker-compose &> /dev/null; then
    echo -e "${ERROR} Both Docker and Docker-Compose must already be installed to use this script."
    echo "        Please ensure both are installed and available using commands docker and"
    echo "        docker-compose, then run this script again."
    exit 1
else echo -e "${OK} Docker and Docker-Compose Installation verified"
fi

# Check to ensure the current user can access the Docker daemon
# https://github.com/rr-/szurubooru/pull/366#discussion_r541795141
if [[ "$(docker ps &> /dev/null | echo $?)" > 0 ]]; then
    echo -e "${ERROR} Unable to verify Docker daemon access!  Please ensure the current user is"
    echo "        authorized for Docker access."
    echo "        For more info, see the official Docker documentation at"
    echo "        https://docs.docker.com/engine/install/linux-postinstall/#manage-docker-as-a-non-root-user"
    exit 1
else echo "${OK} Docker Daemon accessible."
fi


########################################################################################
# Check CPU architectue and, if ARM (such as Raspberry Pi), modify docker-compose.yml
# https://github.com/rr-/szurubooru/wiki/ARM-and-Raspberry-Pi-Support-for-Docker-builds
# https://github.com/rr-/szurubooru/blob/master/docker-compose.yml
########################################################################################

if [[ "$(uname -m)" == 'a'* ]]; then
    echo -e "${NOTICE} ARM architecture detected.  Modfying docker-compose.yml for local build."
    # Modify docker-compose file to build locally instead of pulling from dockerhub
    sed -zi "s|image: szurubooru/server:latest|build:\n      context: ./server|; \
             s|image: szurubooru/client:latest|build:\n      context: ./client|" ./docker-compose.yml
fi


function server_config () {
    ################################################################################################################
    # Copy ./server/config.yaml.dist to ./server/config.yaml, then prompt the user to set the basic config settings
    # https://github.com/rr-/szurubooru/blob/master/doc/INSTALL.md
    ################################################################################################################

    cp server/config.yaml.dist server/config.yaml
    echo -e "\n===[General Settings]==="

    # Prompt for Secret, proposing a randomly generated 32-character alphanumeric value as a default.
    default_secret="$(tr -dc '[:alnum:]' < /dev/urandom | dd bs=4 count=8 2>/dev/null)"
    echo "Enter your Secret (Used to salt the users' password hashes and generate filenames for static content)"
    read -e -p "> " -i "$default_secret" SECRET
    sed -i "s|secret: change|secret: $SECRET|" ./server/config.yaml

    # Other useful (but less important) settings
    echo "Enter the desired name for your server. (Shown in the website title and on the front page)"
    read -e -p "> " -i "szurubooru" SERVERNAME
    echo "Enter the full url to the homepage of this szurubooru site, with no trailing slash."
    read -e -p "> " URL
    sed -i "s|name: szurubooru|name: $SERVERNAME|;s|domain: |domain: $URL|" ./server/config.yaml

    # SMTP (email) settings
    echo -e "\n===[SMTP (Email) Settings]==="
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
                s|from: |from: $SMTP_FROM|" ./server/config.yaml
    else # Warn user that they should set a contact email if no smtp host is specified
        echo -e "${NOTICE} No SMTP host specified!  It is strongly recommended you set a contact email"
    echo "          in the next prompt for manual password reset requests."
    fi

    echo "Enter your server's primary contact email address."
    read -e -p "> " CONTACT_ADDR
    if [ -n "$CONTACT_ADDR" ]; then sed -i "s|contact_email: |contact_email: $CONTACT_ADDR |" ./server/config.yaml; fi
}

function set_env () {
    ############################################################################################################
    # Copy ./doc/example.env to ./.env, then prompt the user to set the basic config settings
    # https://github.com/rr-/szurubooru/blob/master/doc/INSTALL.md
    ############################################################################################################
    cp doc/example.env .env
    echo -e "\n===[Environmental Variables]==="
    echo "Enter your desired database username."; read -e -p "> " -i "szuru" DB_USER
    while true; do # Ensures the user sets a database password for security reasons.
    echo "Enter your desired database password. (Will not print to console)"; read -s -p "> " DB_PASS
        if [ -z $DB_PASS ]; then echo -e "\nERROR: You must set a password!"; else break; fi
    done
    echo ""
    echo "Enter the build info you'd like to display on the home screen."; read -e -p "> " -i "latest" BUILD_INFO
    echo "Enter the port # to expose the HTTP service to."
    echo "Set to 127.0.0.1:8080 if you wish to reverse proxy the docker's port."; read -e -p "> " -i "8080" PORT
    echo "Enter the URL base to run szurubooru under"; read -e -p "> " -i "/" BASE_URL
    echo "Enter the directory in which you wish to store image data."; read -e -p "> " -i "/var/local/szurubooru/data" MOUNT_DATA
    echo "Enter the directory in which you wish to store database files."; read -e -p "> " -i "/var/local/szurubooru/sql" MOUNT_SQL
    sed -i "s|POSTGRES_USER=szuru|POSTGRES_USER=$DB_USER|; \
            s|POSTGRES_PASSWORD=changeme|POSTGRES_PASSWORD=$DB_PASS|; \
            s|BUILD_INFO=latest|BUILD_INFO=$BUILD_INFO|; \
            s|PORT=8080|PORT=$PORT|; \
            s|BASE_URL=/|BASE_URL=$URL|; \
            s|MOUNT_DATA=/var/local/szurubooru/data|MOUNT_DATA=$MOUNT_DATA|; \
            s|MOUNT_SQL=/var/local/szurubooru/sql|MOUNT_SQL=$MOUNT_SQL|" .env
}

echo "Creating and setting up server configuration (server/config.yaml)..."
server_config # Configuration via ./server/config.yaml

echo -e "\nCreating and setting up environmental variables (.env)..."
set_env # Configuration via ./.env

echo -e "\nConfig is all done!  Now pulling Docker containers..."
docker-compose pull # Download containers

echo "Starting SQL container..."
docker-compose up -d sql # Start SQL first

echo "Waiting 30s to ensure the database is ready for connection..."
sleep 30 # Give the database time to become available

echo "Starting Server and Client containers..."
docker-compose up -d # Start remaining containers

# Ensure files can be uploaded by setting ownership of the /data/ mount point
puid=$(grep "PUID=" server/Dockerfile | sed "s/.*=//")
guid=$(grep "PGID=" server/Dockerfile | sed "s/.*=//")
mount=$(grep "MOUNT_DATA" .env | sed 's/MOUNT_DATA=//')
echo "Performing a quick ownership change of $mount to make sure images can be submitted..."
chown -R $puid:$guid "$mount"

echo "All done!  You should now be able to access Szurubooru using the port number you set."
exit 0
