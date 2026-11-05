# Nucleon 1.3 reference image (PHP 7.3 + Phalcon 3.4), used only to measure
# the 1.3 baseline that 2.x performance is compared against.
FROM php:7.3-cli

ARG PHALCON_VERSION=v3.4.5

# PHP 7.3 images are based on an archived Debian release.
RUN . /etc/os-release \
    && echo "deb http://archive.debian.org/debian ${VERSION_CODENAME} main" > /etc/apt/sources.list \
    && echo "deb http://archive.debian.org/debian-security ${VERSION_CODENAME}-security main" >> /etc/apt/sources.list \
    && apt-get -o Acquire::Check-Valid-Until=false update \
    && apt-get install -y --no-install-recommends git unzip $PHPIZE_DEPS \
    && git clone --depth=1 --branch ${PHALCON_VERSION} https://github.com/phalcon/cphalcon.git /tmp/cphalcon \
    && cd /tmp/cphalcon/build && ./install \
    && echo "extension=phalcon.so" > /usr/local/etc/php/conf.d/docker-php-ext-phalcon.ini \
    && docker-php-ext-install opcache \
    && rm -rf /tmp/cphalcon /var/lib/apt/lists/*

COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer

RUN { echo 'memory_limit = 512M'; echo 'opcache.enable_cli = 1'; } > /usr/local/etc/php/conf.d/zz-nucleon.ini

WORKDIR /app
