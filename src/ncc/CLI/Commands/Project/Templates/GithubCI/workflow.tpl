name: CI

on:
  push:
    branches:
      - '**'
  release:
    types: [created]
  workflow_dispatch:

jobs:
${BUILD_JOBS}
  # Check for phpunit.xml
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
            echo "phpunit-exists=true" >> $GITHUB_OUTPUT
          else
            echo "phpunit-exists=false" >> $GITHUB_OUTPUT
          fi

  # Check for phpdoc.dist.xml
  check-phpdoc:
    runs-on: ubuntu-latest
    outputs:
      phpdoc-exists: ${{ steps.check.outputs.phpdoc-exists }}
    steps:
      - name: Checkout repository
        uses: actions/checkout@v4
      - name: Check for phpdoc.dist.xml
        id: check
        run: |
          if [ -f phpdoc.dist.xml ]; then
            echo "phpdoc-exists=true" >> $GITHUB_OUTPUT
          else
            echo "phpdoc-exists=false" >> $GITHUB_OUTPUT
          fi
${PHPDOC_JOB}
${PHPUNIT_JOB}
${RELEASE_DOCUMENTATION_JOB}
${RELEASE_ARTIFACTS_JOB}
