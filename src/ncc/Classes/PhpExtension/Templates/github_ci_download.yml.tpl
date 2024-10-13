      - name: Download %TPL_BUILD_NAME% artifact
        uses: actions/download-artifact@v4
        with:
          name: %TPL_BUILD_NAME%
          path: %TPL_BUILD_NAME%
      - name: Upload %TPL_BUILD_NAME% artifact to release
        uses: softprops/action-gh-release@v1
        with:
            files: |
                %TPL_BUILD_NAME%/*
        env:
            GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}