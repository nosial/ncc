FROM ghcr.io/nosial/ncc:fpm

LABEL org.opencontainers.image.title="${ASSEMBLY_NAME}" \
      org.opencontainers.image.version="${ASSEMBLY_VERSION}" \
      ncc.package="${PACKAGE_NAME}"

WORKDIR /app
COPY . /app

RUN ncc project install -y && ncc build --configuration=${BUILD_CONFIGURATION}
RUN ncc package install --package=${BUILD_OUTPUT} -y