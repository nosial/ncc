name: CI

on:
  push:
    branches:
      - '**'
  release:
    types: [created]
  workflow_dispatch:

jobs:
%TPL_BUILDS%

  # Checking for phpunit.xml
  check-phpunit:
    runs-on: ubuntu-latest
    outputs:
      phpunit-exists: ${{ steps.check.outputs.phpunit-exists }}
    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
      - name: Check for phpunit.xml
        id: check
        run: |
          if [ -f phpunit.xml ]; then
            echo "phpunit-exists=true" >> $GITHUB_OUTPUT
          else
            echo "phpunit-exists=false" >> $GITHUB_OUTPUT
          fi
  # Checking for phpdoc.dist.xml
  check-phpdoc:
    runs-on: ubuntu-latest
    outputs:
      phpdoc-exists: ${{ steps.check.outputs.phpdoc-exists }}
    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
      - name: Check for phpdoc.dist.xml
        id: check
        run: |
          if [ -f phpdoc.dist.xml ]; then
            echo "phpdoc-exists=true" >> $GITHUB_OUTPUT
          else
            echo "phpdoc-exists=false" >> $GITHUB_OUTPUT
          fi
  generate-phpdoc:
    needs: [%TPL_DEFAULT_BUILD_CONFIGURATION%, check-phpdoc]
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

  test:
    needs: [%TPL_BUILD_NAMES%, check-phpunit]
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: needs.check-phpunit.outputs.phpunit-exists == 'true'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Download build artifacts
        uses: actions/download-artifact@v4
        with:
          name: %TPL_DEFAULT_BUILD_CONFIGURATION%
          path: %TPL_DEFAULT_BUILD_CONFIGURATION%

      - name: Install dependencies
        run: |
          apt update -yqq
          apt install git libpq-dev libzip-dev zip make wget gnupg -yqq
          curl -sSLf -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions
          chmod +x /usr/local/bin/install-php-extensions
          install-php-extensions zip

      - name: Install phive
        run: |
          wget -O phive.phar https://phar.io/releases/phive.phar
          wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc
          gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79
          gpg --verify phive.phar.asc phive.phar
          chmod +x phive.phar
          mv phive.phar /usr/local/bin/phive

      - name: Install phab
        run: |
          phive install phpab --global --trust-gpg-keys 0x2A8299CE842DD38C

      - name: Install latest version of NCC
        run: |
          git clone https://git.n64.cc/nosial/ncc.git
          cd ncc
          make redist
          NCC_DIR=$(find build/ -type d -name "ncc_*" | head -n 1)
          if [ -z "$NCC_DIR" ]; then
            echo "NCC build directory not found"
            exit 1
          fi
          php "$NCC_DIR/INSTALL" --auto
          cd .. && rm -rf ncc

      - name: Install NCC packages
        run: |
          ncc package install --package="%TPL_DEFAULT_ARTIFACT_BUILD_OUTPUT%" --build-source --reinstall -y --log-level debug

      - name: Run PHPUnit tests
        run: |
          wget https://phar.phpunit.de/phpunit-11.3.phar
          php phpunit-11.3.phar --configuration phpunit.xml --log-junit reports/junit.xml --log-teamcity reports/teamcity --testdox-html reports/testdox.html --testdox-text reports/testdox.txt

      - name: Upload test reports
        uses: actions/upload-artifact@v4
        with:
          name: reports
          path: reports


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


  release-artifacts:
    needs: [%TPL_BUILD_NAMES%]
    permissions: write-all
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: github.event_name == 'release'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

%TPL_DOWNLOAD_ARTIFACTS%