  %TPL_BUILD_NAME%:
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
          ncc build --config %TPL_BUILD_NAME% --build-source --log-level debug

      - name: Upload build artifact
        uses: actions/upload-artifact@v4
        with:
          name: %TPL_BUILD_NAME%
          path: %TPL_BUILD_OUTPUT%