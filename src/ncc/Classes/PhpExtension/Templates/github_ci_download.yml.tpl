      - name: Download %TPL_BUILD_NAME% artifact
        uses: actions/download-artifact@v4
        with:
          name: %TPL_BUILD_NAME%
          path: %TPL_BUILD_NAME%