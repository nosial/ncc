#
#   ncc in Docker
#
#   This image is intended to be used as a base for projects using ncc.
#

# Build-time args
ARG PHP_VERSION=8.3
ARG FPM_ENABLED=-fpm

# Builder stage: builds ncc phar from source
FROM php:${PHP_VERSION}-fpm AS builder
WORKDIR /tmp

# Install build dependencies
RUN apt-get update -yqq && \
    apt-get install git libpq-dev libzip-dev zip make -yqq

# Copy the local repository to the image
COPY . /tmp/ncc

# Build ncc phar
RUN cd /tmp/ncc && make target/ncc.phar


# Main stage: extends standard PHP image with ncc pre-installed
FROM php:${PHP_VERSION}${FPM_ENABLED} AS production

# OSI labels
LABEL maintainer="Netkas <netkas@nosial.net>"
LABEL description="ncc's official Docker image"

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Copy built ncc phar and installer from builder stage
COPY --from=builder /tmp/ncc/target/ncc.phar /tmp/ncc.phar
COPY --from=builder /tmp/ncc/target/install.sh /tmp/install.sh

# Install runtime dependencies
RUN apt-get update -yqq && \
    apt-get install -yqq git libpq-dev libzip-dev zip make wget -yqq

# Install PHP extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
	install-php-extensions zip xsl msgpack

# Install ncc using the installer script
RUN chmod +x /tmp/install.sh && \
    /tmp/install.sh install && \
    rm -f /tmp/ncc.phar /tmp/install.sh

# Set up default working directory
RUN mkdir -p /app
WORKDIR /app