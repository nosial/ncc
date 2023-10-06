# Variables
PHPCC := $(shell which php)
PHPAB := $(shell which phpab)
DEBPKG := $(shell which dpkg-deb)
SRC_PATH := src
ASSETS_PATH := assets
INSTALLER_SRC_PATH := $(SRC_PATH)/installer
CONFIG_PATH := $(SRC_PATH)/config
BUILD_VERSION := $(shell cat $(SRC_PATH)/ncc/VERSION)
BUILD_PATH := build
GENERIC_BUILD_PATH := $(BUILD_PATH)/ncc_$(BUILD_VERSION)
TAR_BUILD:= ncc_$(BUILD_VERSION).tar.gz
DEBIAN_SRC_PATH := $(SRC_PATH)/debian
DEBIAN_BUILD_PATH := $(BUILD_PATH)/debian/ncc_$(BUILD_VERSION)_all
DEBIAN_PACKAGE_BUILD_PATH := $(BUILD_PATH)/ncc_$(BUILD_VERSION)_all.deb

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
	rm -rf $(GENERIC_BUILD_PATH)
	mkdir -p $(GENERIC_BUILD_PATH)
	cp -rf $(SRC_PATH)/ncc/* $(GENERIC_BUILD_PATH)
	cp -f $(INSTALLER_SRC_PATH)/installer $(GENERIC_BUILD_PATH)/INSTALL
	cp -f $(INSTALLER_SRC_PATH)/ncc.sh $(GENERIC_BUILD_PATH)/ncc.sh
	cp -f $(CONFIG_PATH)/ncc.yaml $(GENERIC_BUILD_PATH)/default_config.yaml
	cp -f $(CONFIG_PATH)/ncc.yaml $(GENERIC_BUILD_PATH)/CLI/template_config.yaml
	cp -f $(CONFIG_PATH)/default_repositories.json $(GENERIC_BUILD_PATH)/default_repositories.json
	cp -f $(INSTALLER_SRC_PATH)/ncc-package.xml $(GENERIC_BUILD_PATH)/ncc-package.xml
	cp -f $(INSTALLER_SRC_PATH)/extension $(GENERIC_BUILD_PATH)/extension
	chmod +x $(GENERIC_BUILD_PATH)/INSTALL
	cp -f LICENSE $(GENERIC_BUILD_PATH)/LICENSE
	cp -f README.md $(GENERIC_BUILD_PATH)/README.md
	cp -f $(INSTALLER_SRC_PATH)/hash_check.php $(GENERIC_BUILD_PATH)/hash_check.php
	$(PHPCC) $(GENERIC_BUILD_PATH)/hash_check.php
	rm $(GENERIC_BUILD_PATH)/hash_check.php
	cp -f $(INSTALLER_SRC_PATH)/generate_build_files.php $(GENERIC_BUILD_PATH)/generate_build_files.php
	$(PHPCC) $(GENERIC_BUILD_PATH)/generate_build_files.php
	rm $(GENERIC_BUILD_PATH)/generate_build_files.php

.PHONY: debian_prepare
debian_prepare: autoload
	rm -rf $(DEBIAN_BUILD_PATH)
	mkdir -p $(DEBIAN_BUILD_PATH)
	mkdir -p $(DEBIAN_BUILD_PATH)/DEBIAN
	mkdir -p $(DEBIAN_BUILD_PATH)/usr/share/ncc
	cp -rf $(SRC_PATH)/ncc/* $(DEBIAN_BUILD_PATH)/usr/share/ncc
	cp -rf $(CONFIG_PATH)/ncc.yaml $(DEBIAN_BUILD_PATH)/usr/share/ncc/default_config.yaml
	cp -rf $(CONFIG_PATH)/ncc.yaml $(DEBIAN_BUILD_PATH)/usr/share/ncc/CLI/template_config.yaml
	cp -rf $(CONFIG_PATH)/default_repositories.json $(DEBIAN_BUILD_PATH)/usr/share/ncc/default_repositories.json
	cp -f LICENSE $(DEBIAN_BUILD_PATH)/usr/share/ncc/LICENSE
	mkdir -p $(DEBIAN_BUILD_PATH)/usr/share/mime/packages
	cp -rf $(INSTALLER_SRC_PATH)/ncc-package.xml $(DEBIAN_BUILD_PATH)/usr/share/mime/packages/ncc-package.xml
	mkdir -p $(DEBIAN_BUILD_PATH)/usr/share/applications
	cp -rf $(INSTALLER_SRC_PATH)/ncc.desktop $(DEBIAN_BUILD_PATH)/usr/share/applications/ncc.desktop
	mkdir -p $(DEBIAN_BUILD_PATH)/usr/share/icons
	cp -rf $(ASSETS_PATH)/icon/ncc@256px.png $(DEBIAN_BUILD_PATH)/usr/share/icons/ncc.png
	cp -rf $(INSTALLER_SRC_PATH)/hash_check.php $(DEBIAN_BUILD_PATH)/usr/share/ncc/hash_check.php
	cp -rf $(DEBIAN_SRC_PATH)/control $(DEBIAN_BUILD_PATH)/DEBIAN/control
	cp -rf $(DEBIAN_SRC_PATH)/postinst $(DEBIAN_BUILD_PATH)/DEBIAN/postinst
	chmod +x $(DEBIAN_BUILD_PATH)/DEBIAN/postinst
	cp -rf $(DEBIAN_SRC_PATH)/postrm $(DEBIAN_BUILD_PATH)/DEBIAN/postrm
	chmod +x $(DEBIAN_BUILD_PATH)/DEBIAN/postrm
	cp -rf $(DEBIAN_SRC_PATH)/copyright $(DEBIAN_BUILD_PATH)/DEBIAN/copyright


$(BUILD_PATH)/$(TAR_BUILD): redist
	cd $(GENERIC_BUILD_PATH) && tar -czf ../$(TAR_BUILD) *

.PHONY: tar
tar: $(BUILD_PATH)/$(TAR_BUILD)

$(DEBIAN_PACKAGE_BUILD_PATH): debian_prepare
	$(DEBPKG) --build $(DEBIAN_BUILD_PATH) $(DEBIAN_PACKAGE_BUILD_PATH)

.PHONY: deb
deb: $(DEBIAN_PACKAGE_BUILD_PATH)

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
	@echo "  make tar               - Package the project into a tarball (Generic installer, requires php)"
	@echo "  make deb               - Package the project into a Debian package"
	@echo "  make clean             - Clean the build artifacts"