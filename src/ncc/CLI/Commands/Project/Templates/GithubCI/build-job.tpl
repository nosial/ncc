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

      - name: Install latest version of ncc
        run: |
          git clone https://git.n64.cc/nosial/ncc.git
          cd ncc
          make target/ncc.phar
          chmod +x target/install.sh
          ./target/install.sh
          cd .. && rm -rf ncc

      - name: Build project
        run: |
          ncc build --config ${BUILD_NAME} --log-level debug

      - name: Upload build artifact
        uses: actions/upload-artifact@v4
        with:
          name: ${BUILD_NAME}
          path: ${BUILD_OUTPUT}
