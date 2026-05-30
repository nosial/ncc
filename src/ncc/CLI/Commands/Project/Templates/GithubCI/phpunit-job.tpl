
  test:
    needs: [${BUILD_NAMES}, check-phpunit]
    runs-on: ubuntu-latest
    container:
      image: ghcr.io/nosial/ncc:latest
    if: needs.check-phpunit.outputs.phpunit-exists == 'true'

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Install ncc packages
        run: |
          ncc project install --log-level debug -y
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
