autoload:
	phpab --output src/ncc/autoload.php src/ncc

redist:
	rm -rf build
	mkdir build build/src
	cp -rf src/ncc/* build/src
	cp src/installer/installer build/src/install.sh
	cp LICENSE build/src/LICENSE
	cp README.md build/src/README.md

tar:
	rm -f build/ncc.tar.gz
	cd build/src; tar -czvf ../ncc.tar.gz *