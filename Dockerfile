# main build for the project
FROM php:7.4-cli

# update package tree
RUN apt-get update

# set working directoty of the container
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp

# copy composer so we can run the dependencies installation from our environment
COPY --from=composer /usr/bin/composer /usr/bin/composer

# disable warning about superuser stuff
RUN export COMPOSER_ALLOW_SUPERUSER=1