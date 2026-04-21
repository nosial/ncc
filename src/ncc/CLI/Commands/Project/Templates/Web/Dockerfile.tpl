# --- STAGE 1: BUILDER (Compiles the NCC Package) ---
FROM php:8.3-fpm AS builder

WORKDIR /app

# 1. Install OS dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl libpq-dev libzip-dev libicu-dev zip make wget gnupg libc-dev pkg-config autoconf \
    && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions
RUN docker-php-ext-install -j$(nproc) zip

# 3. Install APCu via PIE
RUN curl -Lo /usr/local/bin/pie https://github.com/php/pie/releases/latest/download/pie.phar \
    && chmod +x /usr/local/bin/pie \
    && pie install --no-interaction apcu/apcu \
    && echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

# 4. Install PECL extensions
RUN pecl install msgpack && docker-php-ext-enable msgpack

# 5. Install ncc
RUN git clone --recurse-submodules https://git.n64.cc/nosial/ncc /tmp/ncc \
    && cd /tmp/ncc && make target/ncc.phar && target/install.sh \
    && mv /tmp/ncc /tmp/ncc-install && cd /

# 6. Copy source and build
COPY . /app
RUN ncc project install -y && ncc build --configuration=web_release


# --- STAGE 2: PRODUCTION ---
FROM php:8.3-fpm AS production

LABEL org.opencontainers.image.title="${ASSEMBLY_NAME}" \
      org.opencontainers.image.version="${ASSEMBLY_VERSION}" \
      ncc.package="${PACKAGE_NAME}"

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor libpq5 libzip-dev pkg-config \
    && rm -rf /var/lib/apt/lists/*

# 1. Install PHP extensions
RUN docker-php-ext-install -j$(nproc) zip

# 2. Copy compiled extensions from builder and enable
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
RUN docker-php-ext-enable apcu msgpack \
    && echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

# 3. Install ncc
COPY --from=builder /tmp/ncc-install /tmp/ncc-install
RUN cd /tmp/ncc-install && ./target/install.sh && cd / && rm -rf /tmp/ncc-install

# 4. Install the compiled package
COPY --from=builder /app/target/web_release/${PACKAGE_NAME}.ncc /tmp/package.ncc
RUN ncc package install --package=/tmp/package.ncc -y && rm /tmp/package.ncc

# 5. Setup web root
RUN mkdir -p /var/www/html
COPY --from=builder /app/web_entry /var/www/html/index.php

WORKDIR /var/www/html

# 6. Configure Nginx & Supervisor
RUN rm -f /etc/nginx/sites-enabled/default
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
