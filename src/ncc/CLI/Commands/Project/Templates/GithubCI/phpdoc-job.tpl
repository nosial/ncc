
  generate-phpdoc:
    needs: [${DEFAULT_BUILD_CONFIG}, check-phpdoc]
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: needs.check-phpdoc.outputs.phpdoc-exists == 'true'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install dependencies
        run: |
          apt update -yqq
          apt install git libpq-dev libzip-dev zip make wget gnupg -yqq

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
