name: CI

on:
  push:
    branches:
      - '**'
  release:
    types: [created]
  workflow_dispatch:

jobs:
  build:
    runs-on: ubuntu-latest
    container:
      image: php:8.3

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install dependencies
        run: |
          apt update -yqq
          apt install git libpq-dev libzip-dev zip make wget gnupg -yqq

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

      - name: Build project
        run: |
          ncc build --config release --log-level debug

      - name: Upload build artifacts
        uses: actions/upload-artifact@v4
        with:
          name: %ASSEMBLY.NAME%_build
          path: build/release/%ASSEMBLY.PACKAGE%.ncc

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
            echo "phpunit-exists=true" >> $GITHUB_ENV
          else
            echo "phpunit-exists=false" >> $GITHUB_ENV

  test:
    needs: [build, check-phpunit]
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: ${{ needs.check-phpunit.outputs.phpunit-exists == 'true' }}

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Download build artifacts
        uses: actions/download-artifact@v4
        with:
          name: %ASSEMBLY.NAME%_build
          path: %ASSEMBLY.NAME%_build  # Adjust this to download the artifact directly under '%ASSEMBLY.NAME%_build'

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
          ncc package install --package="%ASSEMBLY.NAME%_build/%ASSEMBLY.PACKAGE%.ncc" --build-source --reinstall -y --log-level debug

      - name: Run PHPUnit tests
        run: |
          wget https://phar.phpunit.de/phpunit-11.3.phar
          php phpunit-11.3.phar --configuration phpunit.xml

  release:
    needs: [build, test]
    permissions: write-all
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: github.event_name == 'release'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Download build artifacts
        uses: actions/download-artifact@v4
        with:
          name: %ASSEMBLY.NAME%_build
          path: %ASSEMBLY.NAME%_build

      - name: Upload to GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          files: |
            %ASSEMBLY.NAME%_build/%ASSEMBLY.PACKAGE%.ncc
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}