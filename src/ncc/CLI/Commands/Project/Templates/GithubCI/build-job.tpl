  ${BUILD_NAME}:
    runs-on: ubuntu-latest
    container:
      image: ghcr.io/nosial/ncc:latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v7

      - name: Build project
        run: |
          ncc project install --log-level debug -y
          ncc build --config ${BUILD_NAME} --log-level debug

      - name: Upload build artifact
        uses: actions/upload-artifact@v7
        with:
          name: ${BUILD_NAME}
          path: ${BUILD_OUTPUT}
