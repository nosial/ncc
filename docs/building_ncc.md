# Building NCC from source

Building NCC from source is easy with very few requirements
to start building. At the moment ncc can only be debugged
or tested  by building a redistributable source and
installing it.

## Requirements to build

 - php8.0+
 - php-mbstring
 - php-ctype
 - php-tokenizer *(or php-common)*
 - make
 - phpab
 - tar *(optional)*

## Installing phpab

phpab is also known as [PHP Autoload Builder](https://github.com/theseer/Autoload),
phpab is an open source tool used for creating autoload
files, ncc needs this tool in order to generate it's
autoload files whenever there's any changes to its source
code. 

This tool is only required for building and or creating a
redistributable package of ncc. This component is not
required to be installed to use ncc.

for some components that require static loading, ncc will
automatically load it using it's own 
[autoloader](../src/autoload/autoload.php)

The recommended way to install phpab is by using [phive](https://phar.io/),
if you don't have phive installed you can install it by
running these commands in your terminal (from the official documentation)

```shell
wget -O phive.phar https://phar.io/releases/phive.phar
wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc
gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79
gpg --verify phive.phar.asc phive.phar
chmod +x phive.phar
sudo mv phive.phar /usr/local/bin/phive
```

Once phive is installed, you can run the final command to
install phpab
```shell
phive install phpab
```

**Note:** Optionally, you may want to have `phab` available in your
`$PATH`, this can be done with this command. *(Replace `x.xx.x` with your
version number)*

```shell
ln -s /home/user/.phive/phars/phpab-x.xx.x.phar /usr/bin/phpab
```

## Building NCC

First, navigate to the main directory of NCC's source code
where the [Makefile](../Makefile) is present. If you
already attempted to or had built ncc before, it's
recommended to use `make clean` before building.


### Redist

Running `redist` from the Makefile will generate all the
required autoloaders for ncc and move all the required
files into one redistributable source folder under a
directory called `build/src`

```shell
make redist
```


### Tar

Running `tar` will run redist before packaging the
redistributable source into a tar.gz file that can
be distributed to other machines, this process is not
a requirement.

```shell
make tar
```

Once you have a populated `build/src` folder, you can
simply run execute the `installer` file to install your
build of ncc onto the running machine.