
  release-documentation:
    needs: generate-phpdoc
    permissions: write-all
    runs-on: ubuntu-latest
    container:
      image: php:8.5
    if: github.event_name == 'release'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v7

      - name: Download documentation artifact
        uses: actions/download-artifact@v8
        with:
          name: documentation
          path: documentation

      - name: Upload documentation artifact
        uses: softprops/action-gh-release@v3
        with:
            files: |
                documentation/*
        env:
            GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
