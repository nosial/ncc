# syntax=docker/dockerfile:1
# --- STAGE 1: BUILDER (Compiles the NCC Package) ---
FROM ghcr.io/nosial/ncc:builder AS builder

WORKDIR /app

COPY . /app
RUN ncc project install -y && ncc build --configuration=web_release


# --- STAGE 2: PRODUCTION ---
FROM ghcr.io/nosial/dynamicalweb:latest

LABEL org.opencontainers.image.title="${ASSEMBLY_NAME}" \
      org.opencontainers.image.version="${ASSEMBLY_VERSION}" \
      ncc.package="${PACKAGE_NAME}"

RUN --mount=type=bind,from=builder,source=/app/target/web_release/${PACKAGE_NAME}.ncc,target=/tmp/package.ncc \
    ncc package install --package=/tmp/package.ncc -y

RUN mkdir -p /var/www/html
COPY --from=builder /app/web_entry /var/www/html/index.php

WORKDIR /var/www/html
EXPOSE 8080
