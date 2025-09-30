SOURCE_PATH = src/ncc
TARGET_DIR = target
DEPENDENCY_PAL = dependencies/pal/src/pal/Autoloader.php
DEPENDENCY_LOGLIB2 = dependencies/LogLib2/src/LogLib2/*
DEPENDENCY_PHP_PARSER = dependencies/PHP-Parser/lib/PhpParser/*

.PHONY: build clean dependencies

all: target

dependencies: src/ncc/Libraries/pal \
	src/ncc/Libraries/LogLib2 \
	src/ncc/Libraries/PhpParser \
	src/ncc/Libraries/ctype \
	src/ncc/Libraries/deprecation-contracts \
	src/ncc/Libraries/Yaml

clean:
	rm -rf $(TARGET_DIR)
	rm -rf src/ncc/Libraries/*

target: clean
	make dependencies
	mkdir -p $(TARGET_DIR)
	cp -r $(SOURCE_PATH)/* $(TARGET_DIR)/
	make target/Autoloader.php

target/Autoloader.php:
	php -r " \
		require 'src/ncc/Libraries/pal/Autoloader.php'; \
		use function ncc\Libraries\pal\generate_autoloader; \
		file_put_contents('$(TARGET_DIR)/Autoloader.php', generate_autoloader('$(TARGET_DIR)')); \
	"

src/ncc/Libraries/pal:
	mkdir -p src/ncc/Libraries/pal
	cp $(DEPENDENCY_PAL) src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/namespace pal;/namespace ncc\\Libraries\\pal;/g' src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/use function pal\\/use function ncc\\Libraries\\pal\\/g' src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/\\pal\\Autoloader/\\ncc\\Libraries\\pal\\Autoloader/g' src/ncc/Libraries/pal/Autoloader.php
	sed -i 's/return Autoloader::/return \\ncc\\Libraries\\pal\\Autoloader::/g' src/ncc/Libraries/pal/Autoloader.php

src/ncc/Libraries/LogLib2:
	mkdir -p src/ncc/Libraries/LogLib2
	cp -r $(DEPENDENCY_LOGLIB2) src/ncc/Libraries/LogLib2/
	find src/ncc/Libraries/LogLib2 -name "*.php" -exec sed -i \
		-e 's/namespace LogLib2;/namespace ncc\\Libraries\\LogLib2;/g' \
		-e 's/namespace LogLib2\\/namespace ncc\\Libraries\\LogLib2\\/g' \
		-e 's/use LogLib2\\/use ncc\\Libraries\\LogLib2\\/g' \
		{} \;

src/ncc/Libraries/PhpParser:
	mkdir -p src/ncc/Libraries/PhpParser
	cp -r $(DEPENDENCY_PHP_PARSER) src/ncc/Libraries/PhpParser/
	# Transform namespaces and references for PHP-Parser
	find src/ncc/Libraries/PhpParser -name "*.php" -exec sed -i \
		-e 's/namespace PhpParser;/namespace ncc\\Libraries\\PhpParser;/g' \
		-e 's/namespace PhpParser\\/namespace ncc\\Libraries\\PhpParser\\/g' \
		-e 's/use PhpParser\\/use ncc\\Libraries\\PhpParser\\/g' \
		{} \;
	# Transform hardcoded string references to PhpParser namespace
	# TODO: Bug: `src/ncc/Libraries/PhpParser/compatibility_tokens.php` is not correctly transformed,
	#		the original `PhpParser\defineCompatibilityTokens` function call remains.
	find src/ncc/Libraries/PhpParser -name "*.php" -exec sed -i \
		-e "s|'PhpParser\\\\Node\\\\|'ncc\\\\Libraries\\\\PhpParser\\\\Node\\\\|g" \
		-e 's|PhpParser\\\\defineCompatibilityTokens|ncc\\\\Libraries\\\\PhpParser\\\\defineCompatibilityTokens|g' \
	  	{} \;

src/ncc/Libraries/ctype:
	mkdir -p src/ncc/Libraries/ctype
	cp dependencies/polyfill-ctype/bootstrap80.php src/ncc/Libraries/ctype/bootstrap.php
	cp dependencies/polyfill-ctype/Ctype.php src/ncc/Libraries/ctype/Ctype.php
	find src/ncc/Libraries/ctype -name "*.php" -exec sed -i \
		-e 's/namespace Symfony\\Polyfill\\Ctype;/namespace ncc\\Libraries\\ctype;/g' \
		-e 's/namespace Symfony\\Polyfill\\Ctype\\/namespace ncc\\Libraries\\ctype\\/g' \
		-e 's/use Symfony\\Polyfill\\Ctype\\/use ncc\\Libraries\\ctype\\/g' \
		{} \;

src/ncc/Libraries/deprecation-contracts:
	mkdir -p src/ncc/Libraries/deprecation-contracts
	cp dependencies/deprecation-contracts/function.php src/ncc/Libraries/deprecation-contracts/function.php

src/ncc/Libraries/Yaml:
	mkdir -p src/ncc/Libraries/Yaml/Exception
	mkdir -p src/ncc/Libraries/Yaml/Tag
	cp -r dependencies/yaml/Exception/* src/ncc/Libraries/Yaml/Exception/
	cp -r dependencies/yaml/Tag/* src/ncc/Libraries/Yaml/Tag/
	cp dependencies/yaml/*.php src/ncc/Libraries/Yaml/
	find src/ncc/Libraries/Yaml -name "*.php" -exec sed -i \
		-e 's/namespace Symfony\\Component\\Yaml;/namespace ncc\\Libraries\\Yaml;/g' \
		-e 's/namespace Symfony\\Component\\Yaml\\/namespace ncc\\Libraries\\Yaml\\/g' \
		-e 's/use Symfony\\Component\\Yaml\\/use ncc\\Libraries\\Yaml\\/g' \
		{} \;