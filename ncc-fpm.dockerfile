# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.5
ARG DEBIAN_VERSION=trixie

# Compile extensions before copying any ncc source, so code changes reuse this layer.
FROM php:${PHP_VERSION}-fpm-${DEBIAN_VERSION} AS php-base
ADD --chmod=755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/install-php-extensions
RUN for attempt in 1 2 3; do \
        install-php-extensions zip xsl msgpack && break; \
        [ "$attempt" -eq 3 ] && exit 1; \
        sleep 30; \
    done
RUN echo "memory_limit = 2048M" > /usr/local/etc/php/conf.d/memory-limit.ini
COPY --chmod=755 runtime-dependencies.sh /usr/local/bin/ncc-runtime-dependencies

# Export PHP separately from the OS: deleting compilers in a child layer would
# still leave their bytes in the image's inherited layers.
FROM php-base AS runtime-files
RUN ncc-runtime-dependencies > /tmp/runtime-packages.txt && \
    mkdir -p /opt/php && cp -a /usr/local/. /opt/php/ && \
    rm -rf /opt/php/include /opt/php/lib/php/build && \
    rm -f /opt/php/bin/phpize /opt/php/bin/pear /opt/php/bin/peardev \
        /opt/php/bin/pecl /opt/php/bin/install-php-extensions \
        /opt/php/bin/docker-php-ext-* /opt/php/bin/docker-php-source

FROM php-base AS builder
WORKDIR /tmp/ncc
COPY Makefile ./
COPY src ./src
COPY dependencies ./dependencies
ARG NCC_BUILD=unknown
RUN make target/ncc.phar BUILD="$NCC_BUILD"

# Existing latest/fpm tags remain suitable for compiling downstream projects.
FROM php-base AS sdk
LABEL maintainer="Netkas <netkas@nosial.net>"
RUN apt-get update && \
    apt-get install -y --no-install-recommends git libpq-dev libzip-dev zip make wget && \
    rm -rf /var/lib/apt/lists/*
RUN --mount=type=bind,from=builder,source=/tmp/ncc/target,target=/tmp/ncc-install \
    /tmp/ncc-install/install.sh install
WORKDIR /app

# Same Debian release as the PHP donor image, without its compiler toolchain.
FROM debian:${DEBIAN_VERSION}-slim AS runtime
ENV PHP_INI_DIR=/usr/local/etc/php
COPY --from=runtime-files /opt/php/ /usr/local/
COPY --from=php-base /etc/apt/preferences.d/no-debian-php /etc/apt/preferences.d/no-debian-php
RUN --mount=type=bind,from=runtime-files,source=/tmp/runtime-packages.txt,target=/tmp/runtime-packages.txt \
    apt-get update && \
    apt-get install -y --no-install-recommends ca-certificates curl wget \
        $(cat /tmp/runtime-packages.txt) && \
    rm -rf /var/lib/apt/lists/* && \
    mkdir -p /var/www/html && chown www-data:www-data /var/www/html && \
    chmod 1777 /var/www/html
RUN --mount=type=bind,from=builder,source=/tmp/ncc/target,target=/tmp/ncc-install \
    /tmp/ncc-install/install.sh install
WORKDIR /app
ENTRYPOINT ["docker-php-entrypoint"]
EXPOSE 9000
STOPSIGNAL SIGQUIT
CMD ["php-fpm"]

# Preserve the default target for existing docker build -f invocations.
FROM sdk AS production