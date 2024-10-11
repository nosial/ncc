# Variables
DEFAULT_CONFIGURATION ?= %TPL_DEFAULT_BUILD_CONFIGURATION%
LOG_LEVEL = debug

# Default Target
all: %TPL_BUILD_NAMES%

# Build Steps
%TPL_BUILDS%

install: %TPL_DEFAULT_BUILD_CONFIGURATION%
	ncc package install --package=%TPL_DEFAULT_BUILD_PATH% --skip-dependencies --build-source --reinstall -y --log-level $(LOG_LEVEL)

test: %TPL_DEFAULT_BUILD_CONFIGURATION%
	[ -f phpunit.xml ] || { echo "phpunit.xml not found"; exit 1; }
	phpunit

clean:
	rm -rf build

.PHONY: all install test clean %TPL_BUILD_NAMES%