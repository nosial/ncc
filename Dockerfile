#
#   ncc in Docker
#
#   This image is intended to be used as a base for projects using ncc.
#

# Build-time args
ARG PHP_VERSION=8.3
ARG FPM_ENABLED=-fpm

# Builder stage: downloads necessary files and serves them on a silver platter.
FROM php:${PHP_VERSION}-fpm AS builder
ENV GENERIC_BUILD_PATH=/tmp/ncc_build
WORKDIR /tmp

# Install some stuff the default image doesn't come with
RUN apt-get update -yqq && \
    apt-get install git libpq-dev libzip-dev zip make wget gnupg -yqq

# Download phive and install phab
RUN wget -O phive.phar https://phar.io/releases/phive.phar && \
    wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc && \
    gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79 && \
    gpg --verify phive.phar.asc phive.phar && \
    rm phive.phar.asc && chmod +x phive.phar && \
    ./phive.phar install phpab --global --trust-gpg-keys 0x2A8299CE842DD38C

# Copy the local repository to the image
COPY . /tmp/ncc

# Build ncc
RUN cd /tmp/ncc && make redist


# Main stage: Copies build files and installs all dependencies
FROM php:${PHP_VERSION}${FPM_ENABLED} AS production

# OSI labels
LABEL maintainer="Netkas <netkas@nosial.net>"
LABEL description="ncc's official Docker image"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Copy downloaded files
COPY --from=builder /tmp/ncc_build/. .

# Install some stuff the default image doesn't come with
RUN apt-get update -yqq && \
    apt-get install -yqq git libpq-dev libzip-dev zip make wget gnupg gcc -yqq

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
	install-php-extensions zip xsl

# Install ncc
RUN php INSTALL --auto

# Finalize image
RUN mkdir /app
WORKDIR /app
