# Variables
PHPCC := $(shell which php)
PHPAB := $(shell which phpab)
SRC_PATH := src
INSTALLER_PATH := $(SRC_PATH)/installer
CONFIG_PATH := $(SRC_PATH)/config
TIMESTAMP := $(shell date +%Y%m%d%H%M%S)
BUILD_VERSION := $(shell cat $(SRC_PATH)/ncc/VERSION)
BUILD_PATH := build/ncc-$(BUILD_VERSION)

# List of paths for autoloading
AUTOLOAD_PATHS := $(addprefix $(SRC_PATH)/ncc/ThirdParty/, \
    composer/semver \
    defuse/php-encryption \
    jelix/version \
    nikic/PhpParser \
    Symfony/polyfill_ctype \
    Symfony/polyfill_mbstring \
    Symfony/polyfill_uuid \
    Symfony/Process \
    Symfony/Uid \
    Symfony/Filesystem \
    Symfony/Yaml \
    theseer/DirectoryScanner \
)

# Check for necessary binaries
ifndef PHPCC
    $(error "PHP binary not found. Please install PHP or check your PATH")
endif
ifndef PHPAB
    $(error "phpab (PHP Autoload Builder) binary not found. Please install phpab or check your PATH")
endif

# Build rules
$(SRC_PATH)/%/autoload_spl.php:
	$(PHPCC) $(PHPAB) --output $@ $(SRC_PATH)/$*

$(SRC_PATH)/ncc/autoload_spl.php:
	$(PHPCC) $(PHPAB) --output $@ \
		$(SRC_PATH)/ncc/Enums \
		$(SRC_PATH)/ncc/Classes \
		$(SRC_PATH)/ncc/CLI \
		$(SRC_PATH)/ncc/Exceptions \
		$(SRC_PATH)/ncc/Extensions \
		$(SRC_PATH)/ncc/Interfaces \
		$(SRC_PATH)/ncc/Managers \
		$(SRC_PATH)/ncc/Objects \
		$(SRC_PATH)/ncc/Utilities \
	  	$(SRC_PATH)/ncc/ncc.php \
	  	$(SRC_PATH)/ncc/Runtime.php

# Phony targets
.PHONY: autoload
autoload: $(addsuffix /autoload_spl.php, $(AUTOLOAD_PATHS)) $(SRC_PATH)/ncc/autoload_spl.php
	cp -f $(SRC_PATH)/autoload/autoload.php $(SRC_PATH)/ncc/autoload.php

.PHONY: redist
redist: autoload
	rm -rf $(BUILD_PATH)
	mkdir -p $(BUILD_PATH)
	cp -rf $(SRC_PATH)/ncc/* $(BUILD_PATH)
	cp -f $(INSTALLER_PATH)/installer $(BUILD_PATH)/INSTALL
	cp -f $(INSTALLER_PATH)/ncc.sh $(BUILD_PATH)/ncc.sh
	cp -f $(CONFIG_PATH)/ncc.yaml $(BUILD_PATH)/default_config.yaml
	cp -f $(CONFIG_PATH)/ncc.yaml $(BUILD_PATH)/CLI/template_config.yaml
	cp -f $(CONFIG_PATH)/default_repositories.json $(BUILD_PATH)/default_repositories.json
	cp -f $(CONFIG_PATH)/ncc-package.xml $(BUILD_PATH)/ncc-package.xml
	cp -f $(INSTALLER_PATH)/extension $(BUILD_PATH)/extension
	chmod +x $(BUILD_PATH)/INSTALL
	cp -f LICENSE $(BUILD_PATH)/LICENSE
	cp -f README.md $(BUILD_PATH)/README.md
	cp -f $(INSTALLER_PATH)/hash_check.php $(BUILD_PATH)/hash_check.php
	$(PHPCC) $(BUILD_PATH)/hash_check.php
	rm $(BUILD_PATH)/hash_check.php
	cp -f $(INSTALLER_PATH)/generate_build_files.php $(BUILD_PATH)/generate_build_files.php
	$(PHPCC) $(BUILD_PATH)/generate_build_files.php
	rm $(BUILD_PATH)/generate_build_files.php

$(BUILD_PATH)/build_$(TIMESTAMP).tar.gz: redist
	cd $(BUILD_PATH); tar -czvf ../ncc_$(BUILD_VERSION).tar.gz *

.PHONY: tar
tar: $(BUILD_PATH)/build_$(TIMESTAMP).tar.gz

.PHONY: clean
clean:
	rm -rf $(BUILD_PATH)
	rm -f $(SRC_PATH)/ncc/autoload_spl.php
	rm -f $(addsuffix /autoload_spl.php, $(AUTOLOAD_PATHS))

.PHONY: help
help:
	@echo "Available commands:"
	@echo "  make autoload          - Generate autoload files"
	@echo "  make redist            - Prepare the project for redistribution"
	@echo "  make tar               - Package the project into a tarball with timestamp"
	@echo "  make clean             - Clean the build artifacts"