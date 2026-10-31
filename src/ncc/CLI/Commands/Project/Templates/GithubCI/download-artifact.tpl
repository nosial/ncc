      - name: Download ${BUILD_NAME} artifact
        uses: actions/download-artifact@v8
        with:
          name: ${BUILD_NAME}
          path: ${BUILD_NAME}

      - name: Upload ${BUILD_NAME} artifact to release
        uses: softprops/action-gh-release@v3
        with:
            files: |
                ${BUILD_NAME}/*
        env:
            GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
