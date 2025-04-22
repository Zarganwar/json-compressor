FROM php:8.1-cli

RUN apt-get update && apt-get install -y \
    git curl unzip zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /srv
