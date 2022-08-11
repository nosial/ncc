autoload:
	# Generates/creates all the autoloader files
	make src/ncc/ThirdParty/defuse/php-encryption/autoload_spl.php
	make src/ncc/ThirdParty/Symfony/polyfill-ctype/autoload_spl.php
	make src/ncc/ThirdParty/Symfony/polyfill-mbstring/autoload_spl.php
	make src/ncc/ThirdParty/Symfony/Process/autoload_spl.php
	make src/ncc/ThirdParty/Symfony/Uid/autoload_spl.php
	make src/ncc/autoload_spl.php
	cp src/autoload/autoload.php src/ncc/autoload.php

src/ncc/ThirdParty/defuse/php-encryption/autoload_spl.php:
	phpab --output src/ncc/ThirdParty/defuse/php-encryption/autoload_spl.php \
		src/ncc/ThirdParty/defuse/php-encryption

src/ncc/ThirdParty/Symfony/polyfill-ctype/autoload_spl.php:
	phpab --output src/ncc/ThirdParty/Symfony/polyfill-ctype/autoload_spl.php \
		src/ncc/ThirdParty/Symfony/polyfill-ctype

src/ncc/ThirdParty/Symfony/polyfill-mbstring/autoload_spl.php:
	phpab --output src/ncc/ThirdParty/Symfony/polyfill-mbstring/autoload_spl.php \
		src/ncc/ThirdParty/Symfony/polyfill-mbstring

src/ncc/ThirdParty/Symfony/Process/autoload_spl.php:
	phpab --output src/ncc/ThirdParty/Symfony/Process/autoload_spl.php \
		src/ncc/ThirdParty/Symfony/Process

src/ncc/ThirdParty/Symfony/Uid/autoload_spl.php:
	phpab --output src/ncc/ThirdParty/Symfony/Uid/autoload_spl.php \
		src/ncc/ThirdParty/Symfony/Uid

src/ncc/autoload_spl.php:
	phpab --output src/ncc/autoload_spl.php \
		src/ncc/Abstracts \
		src/ncc/CLI \
		src/ncc/Exceptions \
		src/ncc/Extensions \
		src/ncc/Managers \
		src/ncc/Objects \
		src/ncc/Runtime \
		src/ncc/Utilities \
	  	src/ncc/ncc.php

redist: autoload
	rm -rf build
	mkdir build build/src
	cp -rf src/ncc/* build/src
	cp src/installer/installer build/src/install
	chmod +x build/src/install
	cp LICENSE build/src/LICENSE
	cp README.md build/src/README.md
	cp src/installer/hash_check.php build/src/hash_check.php; php build/src/hash_check.php; rm build/src/hash_check.php
	cp src/installer/generate_build_files.php build/src/generate_build_files.php; php build/src/generate_build_files.php; rm build/src/generate_build_files.php

tar:
	cd build/src; tar -czvf ../ncc.tar.gz *

clean:
	rm -rf build
	rm -f src/ncc/autoload_spl.php
	rm -f src/ncc/ThirdParty/defuse/php-encryption/autoload_spl.php
	rm -f src/ncc/ThirdParty/Symfony/polyfill-ctype/autoload_spl.php
	rm -f src/ncc/ThirdParty/Symfony/polyfill-mbstring/autoload_spl.php
	rm -f src/ncc/ThirdParty/Symfony/Process/autoload_spl.php
	rm -f src/ncc/ThirdParty/Symfony/Uid/autoload_spl.php