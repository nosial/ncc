
  release-artifacts:
    needs: [${BUILD_NAMES}]
    permissions: write-all
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: github.event_name == 'release'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

${DOWNLOAD_ARTIFACTS}
