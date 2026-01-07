# --- STAGE 1: BUILDER (Compiles the NCC Package) ---
FROM php:8.3-fpm AS builder

# Set the working directory for the application source code
WORKDIR /app

# 1. Install necessary OS Dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    make \
    wget \
    gnupg \
    libc-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# 2. Install Required PHP Extensions
RUN docker-php-ext-install -j$(nproc) zip

# 3. Install PECL extensions
RUN pecl install msgpack \
    && docker-php-ext-enable msgpack

# 4. Download and Install ncc (PHAR package manager)
RUN echo "Installing ncc package manager..." \
    && git clone --recurse-submodules https://git.n64.cc/nosial/ncc /tmp/ncc \
    && cd /tmp/ncc \
    && make target/ncc.phar \
    && target/install.sh \
    && mv /tmp/ncc /tmp/ncc-install \
    && cd /

# 4. Copy the Application Source Code
COPY . /app

# 5. Install Project Dependencies and Build the NCC Package
RUN ncc project install -y && ncc build --configuration=${BUILD_CONFIGURATION}


# --- STAGE 2: PRODUCTION (Final Runtime Image) ---
FROM php:8.3-fpm AS production

# Metadata labels
LABEL org.opencontainers.image.title="${ASSEMBLY_NAME}" \
      org.opencontainers.image.version="${ASSEMBLY_VERSION}" \
      org.opencontainers.image.vendor="${ASSEMBLY_ORGANIZATION}" \
      org.opencontainers.image.authors="${ASSEMBLY_AUTHOR}" \
      org.opencontainers.image.description="${ASSEMBLY_DESCRIPTION}" \
      org.opencontainers.image.url="${ASSEMBLY_URL}" \
      org.opencontainers.image.licenses="${ASSEMBLY_LICENSE}" \
      ncc.package="${PACKAGE_NAME}" \
      ncc.version="${ASSEMBLY_VERSION}" \
      ncc.entry_point="${WEB_ENTRY_POINT}"

# Install Nginx, Supervisor and other minimal runtime dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    libpq5 \
    libzip-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# 1. Install Required PHP Extensions (runtime only)
RUN docker-php-ext-install -j$(nproc) zip

# 1.1 Install PECL extensions
RUN pecl install msgpack && docker-php-ext-enable msgpack

# 2. Install ncc by running the installation script (sets up PHP environment properly)
COPY --from=builder /tmp/ncc-install /tmp/ncc-install
RUN cd /tmp/ncc-install && ./target/install.sh && cd / && rm -rf /tmp/ncc-install

# 3. Install the compiled package and its dependencies
COPY --from=builder /app/${BUILD_OUTPUT} /tmp/package.ncc
RUN ncc package install --package=/tmp/package.ncc -y && rm /tmp/package.ncc

# 4. Copy the web entry point file
RUN mkdir -p /var/www/html
COPY --from=builder /app/${WEB_ENTRY_POINT_FILE} /var/www/html/index.php
${REQUIRED_FILES_COPY}

# Set working directory
WORKDIR ${WORKING_DIRECTORY}

# 5. Configure Nginx
RUN rm -f /etc/nginx/sites-enabled/default
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 6. Configure Supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 7. Expose port 8080
EXPOSE 8080

# 8. Define the startup command
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]