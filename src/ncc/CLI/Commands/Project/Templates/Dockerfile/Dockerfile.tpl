# syntax=docker/dockerfile:1
ARG NCC_BUILDER_IMAGE=ghcr.io/nosial/ncc:latest
ARG NCC_RUNTIME_IMAGE=ghcr.io/nosial/ncc:latest

FROM ${NCC_BUILDER_IMAGE} AS builder
WORKDIR /app
COPY . /app
# The package is signed when the optional build secrets are provided, they never become an image layer:
#   docker build --secret id=ncc_signing_key,env=NCC_SIGNING_KEY --secret id=ncc_signing_key_passphrase,env=NCC_SIGNING_KEY_PASSPHRASE .
RUN --mount=type=secret,id=ncc_signing_key,env=NCC_SIGNING_KEY \
    --mount=type=secret,id=ncc_signing_key_passphrase,env=NCC_SIGNING_KEY_PASSPHRASE \
    ncc project install -y && ncc build --configuration=${BUILD_CONFIGURATION} && \
    cp "${BUILD_OUTPUT}" /tmp/package.ncc

FROM ${NCC_RUNTIME_IMAGE} AS production
LABEL org.opencontainers.image.title="${ASSEMBLY_NAME}" \
      org.opencontainers.image.version="${ASSEMBLY_VERSION}" \
      ncc.package="${PACKAGE_NAME}"

WORKDIR /app
# Mount the archive for installation so it never becomes an image layer.
RUN --mount=type=bind,from=builder,source=/tmp/package.ncc,target=/tmp/package.ncc \
    ncc package install --package=/tmp/package.ncc -y
