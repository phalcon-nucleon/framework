# Nucleon 2.x development image: PHP 8.3+ with Phalcon 5.
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

ARG PHALCON_VERSION=5.22.1

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions \
        phalcon-${PHALCON_VERSION} \
        apcu \
        intl \
        opcache \
        pcov \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        redis \
        @composer

COPY php.ini /usr/local/etc/php/conf.d/zz-nucleon.ini

WORKDIR /app
