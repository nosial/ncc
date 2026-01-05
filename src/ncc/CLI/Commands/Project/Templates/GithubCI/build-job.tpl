  ${BUILD_NAME}:
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

      - name: Build project
        run: |
          ncc project install --log-level debug -y
          ncc build --config ${BUILD_NAME} --log-level debug

      - name: Upload build artifact
        uses: actions/upload-artifact@v4
        with:
          name: ${BUILD_NAME}
          path: ${BUILD_OUTPUT}
