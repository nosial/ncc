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

FROM php-base AS builder
WORKDIR /tmp/ncc
COPY Makefile ./
COPY src ./src
COPY dependencies ./dependencies
ARG NCC_BUILD=unknown
RUN make target/ncc.phar BUILD="$NCC_BUILD"

# Existing latest/fpm tags remain suitable for compiling downstream projects.
FROM php-base AS production
LABEL maintainer="Netkas <netkas@nosial.net>"
RUN apt-get update && \
    apt-get install -y --no-install-recommends git libpq-dev libzip-dev zip make wget && \
    rm -rf /var/lib/apt/lists/*
RUN --mount=type=bind,from=builder,source=/tmp/ncc/target,target=/tmp/ncc-install \
    /tmp/ncc-install/install.sh install
WORKDIR /app
