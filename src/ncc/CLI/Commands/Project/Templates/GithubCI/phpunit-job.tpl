
  test:
    needs: [${BUILD_NAMES}, check-phpunit]
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
          name: ${DEFAULT_BUILD_CONFIG}
          path: ${DEFAULT_BUILD_CONFIG}

      - name: Install dependencies
        run: |
          apt update -yqq
          apt install git libpq-dev libzip-dev zip make wget gnupg -yqq
          curl -sSLf -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions
          chmod +x /usr/local/bin/install-php-extensions
          install-php-extensions zip

      - name: Install latest version of ncc
        run: |
          git clone https://git.n64.cc/nosial/ncc.git
          cd ncc
          git submodule update --init --recursive
          make target/ncc.phar
          chmod +x target/install.sh
          ./target/install.sh
          cd .. && rm -rf ncc

      - name: Install ncc packages
        run: |
          ncc install --package="${DEFAULT_BUILD_OUTPUT}" -y --log-level debug

      - name: Run PHPUnit tests
        run: |
          wget https://phar.phpunit.de/phpunit-11.3.phar
          php phpunit-11.3.phar --configuration phpunit.xml --log-junit reports/junit.xml --log-teamcity reports/teamcity --testdox-html reports/testdox.html --testdox-text reports/testdox.txt

      - name: Upload test reports
        uses: actions/upload-artifact@v4
        with:
          name: reports
          path: reports
