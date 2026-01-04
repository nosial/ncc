
  release-documentation:
    needs: generate-phpdoc
    permissions: write-all
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: github.event_name == 'release'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Download documentation artifact
        uses: actions/download-artifact@v4
        with:
          name: documentation
          path: documentation

      - name: Upload documentation artifact
        uses: softprops/action-gh-release@v1
        with:
            files: |
                documentation/*
        env:
            GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
