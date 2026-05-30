
  generate-phpdoc:
    needs: [${DEFAULT_BUILD_CONFIG}, check-phpdoc]
    runs-on: ubuntu-latest
    container:
      image: ghcr.io/nosial/ncc:latest
    if: needs.check-phpdoc.outputs.phpdoc-exists == 'true'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Download PHPDocumentor
        run: |
          wget https://phpdoc.org/phpDocumentor.phar
          chmod +x phpDocumentor.phar

      - name: Generate PHPDoc
        run: |
          php phpDocumentor.phar -d src -t docs

      - name: Archive PHPDoc
        run: |
          zip -r docs.zip docs

      - name: Upload PHPDoc
        uses: actions/upload-artifact@v4
        with:
          name: documentation
          path: docs.zip
