
  test:
    needs: [${BUILD_NAMES}, check-phpunit]
    runs-on: ubuntu-latest
    container:
      image: php:8.3
    if: needs.check-phpunit.outputs.phpunit-exists == 'true'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install dependencies
        run: |
          apt update -yqq
          apt install git libpq-dev libzip-dev zip make wget gnupg -yqq
          curl -sSLf -o /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions
          chmod +x /usr/local/bin/install-php-extensions
          install-php-extensions msgpack zip

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
          ncc project install
          ncc build --config=${DEFAULT_BUILD_CONFIG} --log-level debug

      - name: Download PHPUnit
        run: wget https://phar.phpunit.de/phpunit-11.3.phar

      - name: Run PHPUnit tests
        run: |
          mkdir -p "$PWD/reports"
          chmod 777 "$PWD/reports"
          php phpunit-11.3.phar --configuration phpunit.xml --log-junit "$PWD/reports/junit.xml" --log-teamcity "$PWD/reports/teamcity" --testdox-html "$PWD/reports/testdox.html" --testdox-text "$PWD/reports/testdox.txt"

      - name: Upload test reports
        uses: actions/upload-artifact@v4
        with:
          name: reports
          path: reports
