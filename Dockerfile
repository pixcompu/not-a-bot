# main build for the project
FROM php:7.4-cli

# update package tree
RUN apt-get update

# needed to reproduce files from the server in a voice channel ($music command)
RUN apt-get --yes install ffmpeg

# both needed to use composer as it needs to wrap and unwrap dependencies
RUN apt-get --yes install zip
RUN apt-get --yes install unzip

# set working directoty of the container
COPY . /usr/src/not-a-bot
WORKDIR /usr/src/not-a-bot

# copy composer so we can run the dependencies installation from our environment
COPY --from=composer /usr/bin/composer /usr/bin/composer

# disable warning about superuser stuff
RUN export COMPOSER_ALLOW_SUPERUSER=1