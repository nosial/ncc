VERSION := 3.0.5
BUILD := $(shell git rev-parse --short HEAD)

SOURCE_PATH = src/ncc
INSTALLER_PATH = src/install.sh
TARGET_DIR = target
TARGET_BUILD = $(TARGET_DIR)/build
TARGET_PHAR = $(TARGET_DIR)/ncc.phar
DEPENDENCY_PAL = dependencies/pal/src/pal/Autoloader.php
DEPENDENCY_LOGLIB2 = dependencies/LogLib2/src/LogLib2/*
DEPENDENCY_OPTSLIB = dependencies/optslib/src/OptsLib/*
DEPENDENCY_YAML = dependencies/yaml
DEPENDENCY_PROCESS = dependencies/Process
DEPENDENCY_SEMVER = dependencies/semver/src/*
DEPENDENCY_RANDOM_COMPAT = dependencies/random_compat/lib
DEPENDENCY_PHP_ENCRYPTION = dependencies/php-encryption/src
DEPENDENCY_FSLIB = dependencies/fslib/src/fslib

.PHONY: build clean dependencies install

all: target

dependencies: src/ncc/Libraries/pal \
	src/ncc/Libraries/OptsLib \
	src/ncc/Libraries/LogLib2 \
	src/ncc/Libraries/ctype \
	src/ncc/Libraries/deprecation-contracts \
	src/ncc/Libraries/Yaml \
	src/ncc/Libraries/Process \
	src/ncc/Libraries/semver \
	src/ncc/Libraries/random_compat \
	src/ncc/Libraries/PhpEncryption \
	src/ncc/Libraries/fslib

clean:
	rm -rf $(TARGET_DIR)
	rm -rf src/ncc/Libraries/*

install: target/ncc.phar
	./$(TARGET_DIR)/install.sh

target: clean
	make dependencies
	mkdir -p $(TARGET_BUILD)
	cp -r $(SOURCE_PATH)/* $(TARGET_BUILD)/
	make target/Autoloader.php
	echo $(VERSION) > $(TARGET_BUILD)/VERSION
	echo $(BUILD) > $(TARGET_BUILD)/BUILD

target/Autoloader.php:
	php -r " \
		require 'src/ncc/Libraries/pal/Autoloader.php'; \
		use function ncc\Libraries\pal\generate_autoloader; \
		file_put_contents('$(TARGET_BUILD)/Autoloader.php', generate_autoloader('$(TARGET_BUILD)')); \
	"

target/ncc.phar: target
	php -d phar.readonly=0 -r " \
		\$$phar = new Phar('$(TARGET_PHAR)', 0, 'ncc.phar'); \
		\$$phar->buildFromDirectory('$(TARGET_BUILD)'); \
		\$$stub = \$$phar->createDefaultStub('ncc.php'); \
		\$$phar->setStub(\$$stub); \
	"
	chmod +x $(TARGET_PHAR)
	cp $(INSTALLER_PATH) $(TARGET_DIR)/install.sh
	chmod +x $(TARGET_DIR)/install.sh

src/ncc/Libraries/pal:
	mkdir -p src/ncc/Libraries/pal
	cp $(DEPENDENCY_PAL) src/ncc/Libraries/pal/Autoloader.php
	[ -f dependencies/pal/LICENSE ] && cp dependencies/pal/LICENSE src/ncc/Libraries/pal/ || true
	[ -f dependencies/pal/README.md ] && cp dependencies/pal/README.md src/ncc/Libraries/pal/ || true
	sed -i 's/namespace pal;/namespace ncc\\Libraries\\pal;/g' src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/use function pal\\/use function ncc\\Libraries\\pal\\/g' src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/\\pal\\Autoloader/\\ncc\\Libraries\\pal\\Autoloader/g' src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/return Autoloader::/return \\ncc\\Libraries\\pal\\Autoloader::/g' src/ncc/Libraries/pal/Autoloader.php

src/ncc/Libraries/LogLib2:
	mkdir -p src/ncc/Libraries/LogLib2
	cp -r $(DEPENDENCY_LOGLIB2) src/ncc/Libraries/LogLib2/
	[ -f dependencies/LogLib2/LICENSE ] && cp dependencies/LogLib2/LICENSE src/ncc/Libraries/LogLib2/ || true
	[ -f dependencies/LogLib2/README.md ] && cp dependencies/LogLib2/README.md src/ncc/Libraries/LogLib2/ || true
	find src/ncc/Libraries/LogLib2 -name "*.php" -exec sed -i \
		-e 's/namespace LogLib2;/namespace ncc\\Libraries\\LogLib2;/g' \
		-e 's/namespace LogLib2\\/namespace ncc\\Libraries\\LogLib2\\/g' \
		-e 's/use LogLib2\\/use ncc\\Libraries\\LogLib2\\/g' \
		-e 's/use OptsLib\\/use ncc\\Libraries\\OptsLib\\/g' \
		{} \;

src/ncc/Libraries/OptsLib:
	mkdir -p src/ncc/Libraries/OptsLib
	cp -r $(DEPENDENCY_OPTSLIB) src/ncc/Libraries/OptsLib/
	[ -f dependencies/optslib/LICENSE ] && cp dependencies/optslib/LICENSE src/ncc/Libraries/OptsLib/ || true
	[ -f dependencies/optslib/README.md ] && cp dependencies/optslib/README.md src/ncc/Libraries/OptsLib/ || true
	find src/ncc/Libraries/OptsLib -name "*.php" -exec sed -i \
		-e 's/namespace OptsLib;/namespace ncc\\Libraries\\OptsLib;/g' \
		-e 's/namespace OptsLib\\/namespace ncc\\Libraries\\OptsLib\\/g' \
		-e 's/use OptsLib\\/use ncc\\Libraries\\OptsLib\\/g' \
		{} \;

src/ncc/Libraries/ctype:
	mkdir -p src/ncc/Libraries/ctype
	cp dependencies/polyfill-ctype/bootstrap80.php src/ncc/Libraries/ctype/bootstrap.php
	cp dependencies/polyfill-ctype/Ctype.php src/ncc/Libraries/ctype/Ctype.php
	[ -f dependencies/polyfill-ctype/LICENSE ] && cp dependencies/polyfill-ctype/LICENSE src/ncc/Libraries/ctype/ || true
	[ -f dependencies/polyfill-ctype/README.md ] && cp dependencies/polyfill-ctype/README.md src/ncc/Libraries/ctype/ || true
	find src/ncc/Libraries/ctype -name "*.php" -exec sed -i \
		-e 's/namespace Symfony\\Polyfill\\Ctype;/namespace ncc\\Libraries\\ctype;/g' \
		-e 's/namespace Symfony\\Polyfill\\Ctype\\/namespace ncc\\Libraries\\ctype\\/g' \
		-e 's/use Symfony\\Polyfill\\Ctype\\/use ncc\\Libraries\\ctype\\/g' \
		{} \;

src/ncc/Libraries/deprecation-contracts:
	mkdir -p src/ncc/Libraries/deprecation-contracts
	cp dependencies/deprecation-contracts/function.php src/ncc/Libraries/deprecation-contracts/function.php
	[ -f dependencies/deprecation-contracts/LICENSE ] && cp dependencies/deprecation-contracts/LICENSE src/ncc/Libraries/deprecation-contracts/ || true
	[ -f dependencies/deprecation-contracts/README.md ] && cp dependencies/deprecation-contracts/README.md src/ncc/Libraries/deprecation-contracts/ || true

src/ncc/Libraries/Yaml:
	mkdir -p src/ncc/Libraries/Yaml/Exception
	mkdir -p src/ncc/Libraries/Yaml/Tag
	cp -r $(DEPENDENCY_YAML)/Exception/* src/ncc/Libraries/Yaml/Exception/
	cp -r $(DEPENDENCY_YAML)/Tag/* src/ncc/Libraries/Yaml/Tag/
	cp $(DEPENDENCY_YAML)/*.php src/ncc/Libraries/Yaml/
	[ -f dependencies/yaml/LICENSE ] && cp dependencies/yaml/LICENSE src/ncc/Libraries/Yaml/ || true
	[ -f dependencies/yaml/README.md ] && cp dependencies/yaml/README.md src/ncc/Libraries/Yaml/ || true
	find src/ncc/Libraries/Yaml -name "*.php" -exec sed -i \
		-e 's/namespace Symfony\\Component\\Yaml;/namespace ncc\\Libraries\\Yaml;/g' \
		-e 's/namespace Symfony\\Component\\Yaml\\/namespace ncc\\Libraries\\Yaml\\/g' \
		-e 's/use Symfony\\Component\\Yaml\\/use ncc\\Libraries\\Yaml\\/g' \
		{} \;

src/ncc/Libraries/Process:
	mkdir -p src/ncc/Libraries/Process/Exception
	mkdir -p src/ncc/Libraries/Process/Messenger
	mkdir -p src/ncc/Libraries/Process/Pipes
	cp -r $(DEPENDENCY_PROCESS)/Exception/* src/ncc/Libraries/Process/Exception/
	cp -r $(DEPENDENCY_PROCESS)/Messenger/* src/ncc/Libraries/Process/Messenger/
	cp -r $(DEPENDENCY_PROCESS)/Pipes/* src/ncc/Libraries/Process/Pipes/
	cp $(DEPENDENCY_PROCESS)/*.php src/ncc/Libraries/Process/
	[ -f dependencies/Process/LICENSE ] && cp dependencies/Process/LICENSE src/ncc/Libraries/Process/ || true
	[ -f dependencies/Process/README.md ] && cp dependencies/Process/README.md src/ncc/Libraries/Process/ || true
	find src/ncc/Libraries/Process -name "*.php" -exec sed -i \
		-e 's/namespace Symfony\\Component\\Process;/namespace ncc\\Libraries\\Process;/g' \
		-e 's/namespace Symfony\\Component\\Process\\/namespace ncc\\Libraries\\Process\\/g' \
		-e 's/use Symfony\\Component\\Process\\/use ncc\\Libraries\\Process\\/g' \
		{} \;

src/ncc/Libraries/semver:
	mkdir -p src/ncc/Libraries/semver/Constraint
	cp -r $(DEPENDENCY_SEMVER) src/ncc/Libraries/semver/
	cp -r dependencies/semver/src/Constraint/* src/ncc/Libraries/semver/Constraint/
	[ -f dependencies/semver/LICENSE ] && cp dependencies/semver/LICENSE src/ncc/Libraries/semver/ || true
	[ -f dependencies/semver/README.md ] && cp dependencies/semver/README.md src/ncc/Libraries/semver/ || true
	find src/ncc/Libraries/semver -name "*.php" -exec sed -i \
		-e 's/namespace Composer\\Semver;/namespace ncc\\Libraries\\semver;/g' \
		-e 's/namespace Composer\\Semver\\/namespace ncc\\Libraries\\semver\\/g' \
		-e 's/use Composer\\Semver\\/use ncc\\Libraries\\semver\\/g' \
		{} \;

src/ncc/Libraries/random_compat:
	mkdir -p src/ncc/Libraries/random_compat
	cp -r $(DEPENDENCY_RANDOM_COMPAT)/* src/ncc/Libraries/random_compat/
	[ -f dependencies/random_compat/LICENSE ] && cp dependencies/random_compat/LICENSE src/ncc/Libraries/random_compat/ || true
	[ -f dependencies/random_compat/README.md ] && cp dependencies/random_compat/README.md src/ncc/Libraries/random_compat/ || true

src/ncc/Libraries/PhpEncryption:
	mkdir -p src/ncc/Libraries/PhpEncryption/Exception
	cp -r $(DEPENDENCY_PHP_ENCRYPTION)/Exception/* src/ncc/Libraries/PhpEncryption/Exception/
	cp $(DEPENDENCY_PHP_ENCRYPTION)/*.php src/ncc/Libraries/PhpEncryption/
	[ -f dependencies/php-encryption/LICENSE ] && cp dependencies/php-encryption/LICENSE src/ncc/Libraries/PhpEncryption/ || true
	[ -f dependencies/php-encryption/README.md ] && cp dependencies/php-encryption/README.md src/ncc/Libraries/PhpEncryption/ || true
	find src/ncc/Libraries/PhpEncryption -name "*.php" -exec sed -i \
		-e 's/namespace Defuse\\Crypto;/namespace ncc\\Libraries\\PhpEncryption;/g' \
		-e 's/namespace Defuse\\Crypto\\/namespace ncc\\Libraries\\PhpEncryption\\/g' \
		-e 's/use Defuse\\Crypto\\/use ncc\\Libraries\\PhpEncryption\\/g' \
		-e 's/\\Defuse\\Crypto\\/\\ncc\\Libraries\\PhpEncryption\\/g' \
		-e "s/'Defuse\\\\\\\\Crypto\\\\\\\\/'ncc\\\\\\\\Libraries\\\\\\\\PhpEncryption\\\\\\\\/g" \
		-e 's/"Defuse\\\\Crypto\\\\/"ncc\\\\Libraries\\\\PhpEncryption\\\\/g' \
		{} \;

src/ncc/Libraries/fslib:
	mkdir -p src/ncc/Libraries/fslib/Objects
	cp -r $(DEPENDENCY_FSLIB)/Objects/* src/ncc/Libraries/fslib/Objects/
	cp $(DEPENDENCY_FSLIB)/*.php src/ncc/Libraries/fslib/
	[ -f dependencies/fslib/LICENSE ] && cp dependencies/fslib/LICENSE src/ncc/Libraries/fslib/ || true
	[ -f dependencies/fslib/README.md ] && cp dependencies/fslib/README.md src/ncc/Libraries/fslib/ || true
	find src/ncc/Libraries/fslib -name "*.php" -exec sed -i \
		-e 's/namespace fslib;/namespace ncc\\Libraries\\fslib;/g' \
		-e 's/namespace fslib\\/namespace ncc\\Libraries\\fslib\\/g' \
		-e 's/use fslib\\/use ncc\\Libraries\\fslib\\/g' \
		-e 's/use LogLib2\\/use ncc\\Libraries\\LogLib2\\/g' \
		{} \;
