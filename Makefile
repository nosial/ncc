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
ifndef GENERIC_BUILD_PATH
	GENERIC_BUILD_PATH := $(BUILD_PATH)/ncc_$(BUILD_VERSION)
endif
TAR_BUILD:= ncc_$(BUILD_VERSION).tar.gz
DEBIAN_SRC_PATH := $(SRC_PATH)/debian
DEBIAN_BUILD_PATH := $(BUILD_PATH)/debian/ncc_$(BUILD_VERSION)_all
DEBIAN_PACKAGE_BUILD_PATH := $(BUILD_PATH)/ncc_$(BUILD_VERSION)_all.deb

# Third-party dependency management
THIRDPARTY_PATH := $(SRC_PATH)/ncc/ThirdParty
SUBMODULES := $(shell git config --file .gitmodules --get-regexp path | awk '{print $$2}')

# List of paths for autoloading
AUTOLOAD_PATHS := $(addprefix $(SRC_PATH)/ncc/ThirdParty/, \
	composer/Semver \
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

# Check if phpab is installed
ifndef PHPAB
	$(error "phpab (PHP Autoload Builder) not found. Please install phpab or check your PATH")
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

.PHONY: install
install: redist
	$(GENERIC_BUILD_PATH)/INSTALL --auto

.PHONY: docker-debian
docker-debian:
	docker build -t ncc-debian -f Dockerfile.debian .

.PHONY: docker-debian-run
docker-debian-run:
	docker run -it --rm ncc-debian /bin/bash

.PHONY: docker-alpine
docker-alpine:
	docker build -t ncc-alpine -f Dockerfile .

.PHONY: docker-alpine-run
docker-alpine-run:
	docker run -it --rm ncc-alpine /bin/sh

.PHONY: clean
clean:
	rm -rf $(BUILD_PATH)
	rm -f $(SRC_PATH)/ncc/autoload_spl.php
	rm -f $(addsuffix /autoload_spl.php, $(AUTOLOAD_PATHS))

.PHONY: update-dependencies
update-dependencies:
	@echo "Updating git submodules to latest tags..."
	@for submodule in $(SUBMODULES); do \
		echo "Processing submodule: $$submodule"; \
		cd $$submodule && \
		git reset --hard HEAD && \
		git clean -fd && \
		git fetch --tags && \
		latest_tag=$$(git describe --tags --abbrev=0 2>/dev/null || echo ""); \
		if [ -n "$$latest_tag" ]; then \
			echo "Checking out latest tag: $$latest_tag for $$submodule"; \
			git checkout $$latest_tag; \
		else \
			echo "No tags found for $$submodule, using HEAD"; \
			git checkout HEAD; \
		fi && \
		cd - > /dev/null; \
	done
	@echo "Applying namespace patches..."
	@$(MAKE) patch-namespaces
	@echo "Updating VERSION files..."
	@$(MAKE) update-version-files
	@echo "Updating version.json..."
	@$(MAKE) update-version-json
	@echo "Dependencies updated successfully!"

.PHONY: patch-namespaces
patch-namespaces:
	@echo "Patching Symfony component namespaces..."
	@find $(THIRDPARTY_PATH)/Symfony -name "*.php" -type f | while read file; do \
		if grep -q "namespace Symfony\\Component" "$$file" 2>/dev/null; then \
			echo "Patching namespace in $$file"; \
			sed -i 's/namespace Symfony\\Component\\Uid/namespace ncc\\ThirdParty\\Symfony\\Uid/g' "$$file"; \
			sed -i 's/namespace Symfony\\Component\\Process/namespace ncc\\ThirdParty\\Symfony\\Process/g' "$$file"; \
			sed -i 's/namespace Symfony\\Component\\Yaml/namespace ncc\\ThirdParty\\Symfony\\Yaml/g' "$$file"; \
			sed -i 's/namespace Symfony\\Component\\Filesystem/namespace ncc\\ThirdParty\\Symfony\\Filesystem/g' "$$file"; \
		fi; \
		if grep -q "use Symfony\\Component" "$$file" 2>/dev/null; then \
			echo "Patching use statements in $$file"; \
			sed -i 's/use Symfony\\Component\\Uid/use ncc\\ThirdParty\\Symfony\\Uid/g' "$$file"; \
			sed -i 's/use Symfony\\Component\\Process/use ncc\\ThirdParty\\Symfony\\Process/g' "$$file"; \
			sed -i 's/use Symfony\\Component\\Yaml/use ncc\\ThirdParty\\Symfony\\Yaml/g' "$$file"; \
			sed -i 's/use Symfony\\Component\\Filesystem/use ncc\\ThirdParty\\Symfony\\Filesystem/g' "$$file"; \
		fi; \
		if grep -q "Symfony\\\\Component" "$$file" 2>/dev/null; then \
			echo "Patching string references in $$file"; \
			sed -i 's/Symfony\\\\Component\\\\Uid/ncc\\\\ThirdParty\\\\Symfony\\\\Uid/g' "$$file"; \
			sed -i 's/Symfony\\\\Component\\\\Process/ncc\\\\ThirdParty\\\\Symfony\\\\Process/g' "$$file"; \
			sed -i 's/Symfony\\\\Component\\\\Yaml/ncc\\\\ThirdParty\\\\Symfony\\\\Yaml/g' "$$file"; \
			sed -i 's/Symfony\\\\Component\\\\Filesystem/ncc\\\\ThirdParty\\\\Symfony\\\\Filesystem/g' "$$file"; \
		fi; \
		if grep -qE "(\\\\|')Symfony\\\\Component" "$$file" 2>/dev/null; then \
			echo "Patching quoted references in $$file"; \
			sed -i "s/'Symfony\\\\Component\\\\Uid'/'ncc\\\\ThirdParty\\\\Symfony\\\\Uid'/g" "$$file"; \
			sed -i "s/'Symfony\\\\Component\\\\Process'/'ncc\\\\ThirdParty\\\\Symfony\\\\Process'/g" "$$file"; \
			sed -i "s/'Symfony\\\\Component\\\\Yaml'/'ncc\\\\ThirdParty\\\\Symfony\\\\Yaml'/g" "$$file"; \
			sed -i "s/'Symfony\\\\Component\\\\Filesystem'/'ncc\\\\ThirdParty\\\\Symfony\\\\Filesystem'/g" "$$file"; \
		fi; \
	done
	@echo "Patching any remaining references in the entire codebase..."
	@find $(SRC_PATH)/ncc -name "*.php" -type f | while read file; do \
		if grep -qE "Symfony\\\\Component\\\\(Uid|Process|Yaml|Filesystem)" "$$file" 2>/dev/null; then \
			echo "Patching references in $$file"; \
			sed -i 's/Symfony\\Component\\Uid/ncc\\ThirdParty\\Symfony\\Uid/g' "$$file"; \
			sed -i 's/Symfony\\Component\\Process/ncc\\ThirdParty\\Symfony\\Process/g' "$$file"; \
			sed -i 's/Symfony\\Component\\Yaml/ncc\\ThirdParty\\Symfony\\Yaml/g' "$$file"; \
			sed -i 's/Symfony\\Component\\Filesystem/ncc\\ThirdParty\\Symfony\\Filesystem/g' "$$file"; \
		fi; \
	done
	@echo "Removing test and documentation files that may cause issues..."
	@find $(THIRDPARTY_PATH)/Symfony -type d -name "Tests" -exec rm -rf {} + 2>/dev/null || true
	@find $(THIRDPARTY_PATH)/Symfony -name "*.md" -delete 2>/dev/null || true
	@find $(THIRDPARTY_PATH)/Symfony -name "CHANGELOG*" -delete 2>/dev/null || true
	@find $(THIRDPARTY_PATH)/Symfony -name "LICENSE*" -delete 2>/dev/null || true
	@find $(THIRDPARTY_PATH)/Symfony -name "composer.json" -delete 2>/dev/null || true
	@find $(THIRDPARTY_PATH)/Symfony -name "phpunit.xml*" -delete 2>/dev/null || true
	@echo "Handling conditional class definitions..."
	@if [ -f "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php" ]; then \
		echo "Fixing HashableInterface conditional definition..."; \
		mv "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php" "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php.bak"; \
		echo '<?php' > "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo 'namespace ncc\ThirdParty\Symfony\Uid;' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '/**' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo ' * @internal' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo ' */' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo 'interface HashableInterface' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '{' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '    public function equals(mixed $$other): bool;' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '    public function hash(): string;' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
		echo '}' >> "$(THIRDPARTY_PATH)/Symfony/Uid/HashableInterface.php"; \
	fi

.PHONY: update-version-files
update-version-files:
	@echo "Creating VERSION files for dependencies..."
	@for submodule in $(SUBMODULES); do \
		if [ -d "$$submodule" ]; then \
			cd $$submodule && \
			latest_tag=$$(git describe --tags --abbrev=0 2>/dev/null || echo ""); \
			if [ -n "$$latest_tag" ]; then \
				version=$$(echo "$$latest_tag" | sed 's/^v//g' | sed 's/-.*$$//g'); \
				echo "$$version" > VERSION; \
				echo "Created VERSION file for $$submodule with version $$version"; \
			else \
				echo "No tags found for $$submodule, skipping VERSION file creation"; \
			fi && \
			cd - > /dev/null; \
		fi; \
	done

.PHONY: update-version-json
update-version-json:
	@echo "Updating version.json with latest dependency versions..."
	@if [ -f "$(SRC_PATH)/ncc/version.json" ]; then \
		$(PHPCC) -r " \
		\$$json = json_decode(file_get_contents('$(SRC_PATH)/ncc/version.json'), true); \
		\$$submodules = ['$(THIRDPARTY_PATH)/Symfony/Uid', '$(THIRDPARTY_PATH)/Symfony/Process', '$(THIRDPARTY_PATH)/Symfony/Yaml']; \
		foreach (\$$submodules as \$$submodule) { \
			if (file_exists(\$$submodule . '/VERSION')) { \
				\$$version = trim(file_get_contents(\$$submodule . '/VERSION')); \
				\$$componentName = basename(\$$submodule); \
				foreach (\$$json['components'] as &\$$component) { \
					if (\$$component['vendor'] == 'Symfony' && \$$component['package_name'] == \$$componentName) { \
						\$$component['version'] = \$$version; \
						echo \"Updated \$$componentName to version \$$version\n\"; \
						break; \
					} \
				} \
			} \
		} \
		file_put_contents('$(SRC_PATH)/ncc/version.json', json_encode(\$$json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); \
		echo \"version.json updated successfully\n\"; \
		"; \
	else \
		echo "version.json not found, skipping update"; \
	fi

.PHONY: help
help:
	@echo "Available commands:"
	@echo "  make autoload			- Generate autoload files"
	@echo "  make redist			- Prepare the project for redistribution"
	@echo "  make install			- Installs ncc on the system (requires root privileges & php)"
	@echo "  make tar			- Package the project into a tarball (Generic installer, requires php)"
	@echo "  make deb			- Package the project into a Debian package"
	@echo "  make update-dependencies	- Update git submodules to latest tags and patch namespaces"
	@echo "  make patch-namespaces		- Patch Symfony component namespaces to ncc\\ThirdParty"
	@echo "  make update-version-files	- Create VERSION files for dependencies"
	@echo "  make update-version-json	- Update version.json with latest dependency versions"
	@echo "  make docker-debian		- Build a Debian Docker image"
	@echo "  make docker-debian-run	- Run the Debian Docker image"
	@echo "  make docker-alpine		- Build an Alpine Docker image"
	@echo "  make docker-alpine-run	- Run the Alpine Docker image"
	@echo "  make clean			- Clean the build artifacts"
	@echo "  make update-dependencies - Update third-party dependencies and patches"