# ncc Documentation

This document serves the purpose of presenting the documentation for using/developing ncc, from basic installation,
basic usage, standards, and much more.

## Table of contents

<!-- TOC -->
* [ncc Documentation](#ncc-documentation)
  * [Table of contents](#table-of-contents)
  * [Introduction](#introduction)
    * [What is ncc?](#what-is-ncc)
    * [How does ncc work?](#how-does-ncc-work)
    * [What do I need to use ncc?](#what-do-i-need-to-use-ncc)
    * [How do I get started?](#how-do-i-get-started)
* [Building & installing ncc](#building--installing-ncc)
  * [Building from source](#building-from-source)
    * [Requirements to build](#requirements-to-build)
    * [Installing phpab](#installing-phpab)
    * [Building ncc](#building-ncc)
      * [Redist](#redist)
      * [Tar](#tar)
      * [Debian](#debian)
  * [Building ncc for docker](#building-ncc-for-docker)
  * [Installing ncc](#installing-ncc)
    * [Command line arguments](#command-line-arguments)
  * [Uninstalling ncc](#uninstalling-ncc)
* [Conventions](#conventions)
  * [Package Naming](#package-naming)
  * [Remote Package Syntax (RPS)](#remote-package-syntax-rps)
* [ncc cli](#ncc-cli)
  * [Project Management (project)](#project-management-project)
    * [Creating a new project (create)](#creating-a-new-project-create)
    * [Applying Templates (template)](#applying-templates-template)
  * [Package Management (package or pkg)](#package-management-package-or-pkg)
    * [Listing Installed Packages (list)](#listing-installed-packages-list)
    * [Installing Packages (install)](#installing-packages-install)
    * [Uninstalling Packages (uninstall)](#uninstalling-packages-uninstall)
    * [Uninstalling All Packages (uninstall-all)](#uninstalling-all-packages-uninstall-all)
    * [Fix Broken Packages (fix-broken)](#fix-broken-packages-fix-broken)
  * [Credentials Management (cred)](#credentials-management-cred)
    * [Adding a credential (add)](#adding-a-credential-add)
    * [Removing a credential (remove)](#removing-a-credential-remove)
    * [Listing credential entries (list)](#listing-credential-entries-list)
  * [Managing Repositories (repository or repo)](#managing-repositories-repository-or-repo)
    * [Adding a repository (add)](#adding-a-repository-add)
    * [Removing a repository (remove)](#removing-a-repository-remove)
    * [Listing repositories (list)](#listing-repositories-list)
  * [Building Projects (build)](#building-projects-build)
  * [Execute (exec)](#execute-exec)
<!-- TOC -->


------------------------------------------------------------------------------------------------------------------------


## Introduction

This section serves the basic introduction of ncc, what it's used for and how you can use it in your own projects or use 
it to run and build other projects that are designed to be used with ncc. 


### What is ncc?

ncc (*Acronym for **N**osial **C**ode **C**ompiler*) is a multipurpose compiler, package manager and toolkit. Allowing 
projects to be managed and built more easily without having to mess with all the traditional tools that comes with your 
language of choice. Right now ncc only supports PHP as it's written in PHP but extensions for other languages/frameworks
can be built into the software in the future when the need comes for it.

ncc can make the process of building your code into a redistributable package much more efficient by treating each 
building block of your project as a component that is interconnected in your environment instead of the more popular 
route taken by package/dependency managers such as [composer](https://getcomposer.org/) which attempts to copy what [npm](https://www.npmjs.com/) does but for
PHP, which is not a bad thing, but it's not the best approach for PHP and its ecosystem.


### How does ncc work?

ncc's command-line interface serves as the central hub for managing your projects and packages. This CLI empowers you to
perform tasks like project creation, package installation, and project building.

When you compile your project, it generates a "ncc package" file with a ".ncc" extension. This unique file consolidates
all essential project files, including the utilized components. Optionally, you have the flexibility to create a static
version of your project that contains all dependencies and components, simplifying deployment and execution across
different machines through a single file.

ncc harnesses these packages to install its dependencies and components globally, akin to how Composer installs packages
into a project's vendor directory. However, in ncc's case, it installs them into a global directory accessible to other
projects. Moreover, ncc can retrieve packages from various remote sources, including GitHub, GitLab, Gitea, and even 
Packagist. If ncc cannot locate a specific ncc package for a dependency or package, it will attempt to build it from 
source using the package's source code. Additionally, ncc features a compatibility layer for Composer packages, enabling 
you to install them without needing to install or use Composer itself.

You can imagine ncc as apt-get for PHP, but with a few extra features that make it more powerful and flexible.


### What do I need to use ncc?

ncc is a command-line tool, so you will need to be familiar with using the command-line interface. You will also need to
have PHP 8.0+ installed on your machine, along with the following PHP extensions:

- php-mbstring
- php-ctype
- php-common (covers tokenizer & posix among others)
- php-zip

These extensions are required for ncc to function properly, if you don't have these extensions installed, ncc may not
work correctly or may suffer from performance issues. For instance, `php-ctype` & `php-mbstring` are required for ncc
however, they will still work without them thanks to Symfony's polyfill library, but it's recommended to install the
extensions for better performance.


### How do I get started?

[RTFM](https://en.wikipedia.org/wiki/RTFM), this documentation is a good place to start, it covers everything you need
to know about ncc and how to use it.


------------------------------------------------------------------------------------------------------------------------


# Building & installing ncc

ncc must be built from source before it can be installed, this is because ncc is a PHP application and PHP applications
are not compiled into machine code, instead, they are compiled into a redistributable source that can be installed on
the machine. This includes the auto-loader files that ncc needs to locate its components and dependencies.


## Building from source

Building ncc from source is easy with very few requirements to start building. At the moment, ncc can only be debugged or
tested by building a redistributable source and installing it.

### Requirements to build

- php8.0+
- php-mbstring
- php-ctype
- php-common (covers tokenizer & posix among others)
- make
- phpab
- tar *(optional)*

For building different variants, such as building a debian package, you will need to install the required tools for that
specific variant. For more information, check the [Makefile](Makefile) for the required tools for a specific variant.

### Installing phpab

phpab is also known as [PHP Autoload Builder](https://github.com/theseer/Autoload), phpab is an open-source tool used 
for creating autoload files, ncc needs this tool in order to generate its autoload files whenever there are any changes
to its source code. This tool is only required for building and or creating a redistributable package of ncc.
This component is not required to be installed to use ncc.

for some components that require static loading, ncc will automatically load it using its own [autoloader](src/autoload/autoload.php)

The recommended way to install phpab is by using [phive](https://phar.io/), if you don't have phive installed, you can 
install it by running these commands in your terminal (from the official documentation)

```shell
wget -O phive.phar https://phar.io/releases/phive.phar
wget -O phive.phar.asc https://phar.io/releases/phive.phar.asc
gpg --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79
gpg --verify phive.phar.asc phive.phar
chmod +x phive.phar
sudo mv phive.phar /usr/local/bin/phive
```

Once phive is installed, you can run the final command to install phpab

```shell
sudo phive install phpab --global
```

or you can run this command to install it locally

```shell
phive install phpab
```

**Note:** Optionally, you may want to have `phab` available in your `$PATH`, this can be done with this command. 
*(Replace `x.xx.x` with your version number)* this is if you installed it locally

```shell
ln -s /home/user/.phive/phars/phpab-x.xx.x.phar /usr/local/bin/phpab
```

### Building ncc

First, navigate to the main directory of ncc's source code where the [Makefile](Makefile) is present. If you
already attempted to or had built ncc before, it's  recommended to use `make clean` before building.

#### Redist

Running `redist` from the Makefile will generate all the required autoloader for ncc and move all the required files 
into one redistributable source folder under a directory called `build/src`

```shell
make redist
```


#### Tar

Running `tar` will run redist before packaging the redistributable source into a tar.gz file that can be distributed to 
other machines, this process is not a requirement.

```shell
make tar
```


#### Debian

Running `deb` will run `redist` before packaging the redistributable source into a debian package that can be installed
on debian based machines, this process is not a requirement.

```shell
make deb
```

Once you have a populated `build/ncc_x.x.x` folder, you can simply run execute the `installer` file to install your build
of ncc onto the running machine.

 > Note: you may need to run `sudo` before executing the installer file.


------------------------------------------------------------------------------------------------------------------------


## Building ncc for docker

ncc is also available for docker, you can build ncc for docker by running the `docker-debian` or `docker-alpine` make
tasks, this will build ncc for docker and create a docker image that you can use to run ncc.

```shell
make docker-debian docker-alpine
```

The respective docker files are located in 

- [Dockerfile Debian](Dockerfile.debian)
- [Dockerfile Alpine](Dockerfile)

You may also run and test these docker builds with the `docker-debian-run` and `docker-alpine-run` make tasks.

```shell
make docker-debian-run docker-alpine-run
```

 > Contributor Note: contributions are welcomed here to expand ncc's docker support to other distros and to improve the
 > existing docker files.

------------------------------------------------------------------------------------------------------------------------


## Installing ncc

Installing ncc is easy, you can either download the redistributable source from the [releases](https://git.n64.cc/nosial/ncc/-/releases)
page or you can build it from source using the instructions above.

Once you have the redistributable source, you can simply run execute the `INSTALL` file to install ncc onto the running 
machine. usually this installation process will require root privileges, so it's recommended to run the installer with
`sudo` or as root.


### Command line arguments

The installer accepts a few command line arguments that can be used to alter the installation process.

`--help` Displays the help message

`--bypass-cli-check` Bypasses the check in the installer that checks if the installer is being run from the command
line, this is useful if you want to install ncc from a script.

`--bypass-checksum` Bypasses the checksum check in the installer, this is useful if you made modifications to the 
installation files and want to install a modified version of ncc. But this isn't recommended, and the proper way to
do this is to modify the source code and build ncc from source, the Makefile task will automatically rebuild the
checksum file for you.


## Uninstalling ncc

Uninstalling ncc is easy, simply delete the directory where ncc was installed to, by default this is `/etc/ncc`.

It's recommended to run `ncc package --uninstall-all` before uninstalling ncc, this will uninstall all the packages
that were installed using ncc and remove any artifacts that is installed on the system such as symlink registrations
and so on.

**Note:**

- To delete all the data that ncc has created, you can also delete the `/usr/share/ncc` directory.
- Finally, remove the symlink that was created in `/usr/bin`to the `ncc` entry point file.


------------------------------------------------------------------------------------------------------------------------


# Conventions

This section covers the conventions that ncc uses for its source code, documentation, and other things.


## Package Naming

ncc follows a package naming convention that is inspired by Java's package naming convention. This convention is
designed to ensure clarity, avoid naming conflicts, and make it easier to identify the origin of packages. Below
are the key rules for naming packages in ncc:

 - The package name must be in all lowercases
 - The package name must be in reverse domain notation
 - The package name must be separated by a dot `.`
 - The package name must not contain any special characters other than '-' or '_'
 - The package name must not contain any spaces

In PHP, similar to Java, package naming conventions follow the reversed domain notation. This means that a package name,
such as "symfony/process" in Composer, is transformed into "com.symfony.process" in PHP. The components of the package
name are separated by dots (".") to create a hierarchical structure.

It's essential to adhere to specific guidelines when naming packages in PHP:

 - **Character Limitation**: Package names should consist of only hyphens ("-") or underscores ("_") as special
   characters. This restriction ensures compatibility with both file systems and package management tools.
 - **Avoiding Spaces**: Spaces should be avoided in package names. The inclusion of spaces can lead to confusion and
   compatibility issues. Instead, use hyphens or underscores if spacing is necessary within a package name.


## Remote Package Syntax (RPS)

Remote packages are packages that are hosted on a remote source, such as GitHub, GitLab, Gitea, and so on. ncc uses
a special syntax for specifying remote packages, this syntax is called Remote Package Syntax or RPS for short.
This syntax is simply a query to tell ncc what package to install and where to install it from. This syntax is used when
installing packages from the command-line or defining dependencies in a project's package.json file.

The syntax for RPS is as follows:

```
<vendor>/<package-name>=<version>@<repository>
```

| Component      | Description                                                                                                   |
|----------------|---------------------------------------------------------------------------------------------------------------|
| `<vendor>`     | The vendor name of the package, this is usually the username or organization name on the source eg; symfony   |
| `<package>`    | The package name, this is the name of the package eg; console                                                 |
| `<version>`    | The version of the package to install, this can be a version number or simply "latest" for the latest version |
| `<repository>` | The repository to install the package from, this has to be a name of a repository that's configured in ncc    |

**Note:** The version number can be omitted, in which case ncc will install the latest version of the package.

Here are some examples of RPS:

```text
symfony/console=latest@packagist # installs the latest version of symfony/console from packagist
johndoe/hello_world=latest@github # installs the latest version of hello_world from github
```

For instances like Gitlab where organizations may have subgroups, you can specify the subgroup by using a dot (".")
to separate the group name from the subgroup name.

```text
nosial/libs.config@n64 # installs the latest version of ConfigLib from n64
nosial/libs.config=1.0.0@n64 # installs version 1.0.0 of ConfigLib from n64
```


------------------------------------------------------------------------------------------------------------------------


# ncc cli

ncc's command-line interface serves as the central hub for managing your projects and packages. This CLI empowers you to
perform tasks like project creation, package installation, and project building.


## Project Management (project)

The command `project` provides a set of commands for managing projects, such as creating a new project and applying
a builtin template to a project. This part of the documentation will guide you through the process of creating a new
project and applying a template to it both for CLI-based projects and library projects.


### Creating a new project (create)

To create a new project, you can use the `project create` command, this command will create a new project in the current
working directory. You must specify details about the project you want to create, such as the project name, package name,
and the compiler extension to use.

| Option               | Required | Example             | Description                                                                                                   |
|----------------------|----------|---------------------|---------------------------------------------------------------------------------------------------------------|
| `--name`, `-n`       | Yes      | ExampleProject      | The name of the project                                                                                       |
| `--package`, `--pkg` | Yes      | com.example.project | The package name to use, see [Package Naming](#package-naming) for more information                           |
| `--path`, `-p`       | No       | example_project     | The directory to create/use to initialize the project in, if not provided then `--name` would be used instead |

```shell
ncc project create --name ExampleProject --package com.example.project
```

Once the project is created, will see a new directory with the name of the project you specified, this directory will
contain the project's source code and other files that are required for the project to function properly, more importantly
it will contain the `package.json` file which is the project's configuration file that ncc uses to manage and build
the project.

### Applying Templates (template)

Templates are used to apply a predefined structure to a project, this is not a requirement but makes it easier to get
started with a project. ncc comes with a few builtin templates that you can use to create a project

| Template Name | Description                                 |
|---------------|---------------------------------------------|
| `phpcli`      | A template for creating a CLI-based project |
| `phplib`      | A template for creating a library project   |

To apply a template, simply use the `project template` command, this command will apply a template to the project in
the current working directory or the directory specified by the `--path` option.

| Option         | Required | Example         | Description                                                                                                                        |
|----------------|----------|-----------------|------------------------------------------------------------------------------------------------------------------------------------|
| `--name`, `-n` | Yes      | phpcli          | The name of the template to apply                                                                                                  |
| `--path`, `-p` | No       | example_project | The directory to create/use to apply the the template to, if not provided then the current working directory would be used instead |

```shell
ncc project template --name phpcli
```

Once the template is applied, you will see additional files and directories created by ncc; these files and directories
are part of the template and are required for the project to function properly, your project.json file will also be
updated to reflect the changes made by the template.



## Package Management (package or pkg)

The command `package` provides a set of commands for managing packages, such as installing packages and uninstalling
packages, `pkg` is an alias for `package` and can be used interchangeably.

> **Note:** that most of these operations requires root privileges, so it's recommended to run ncc with `sudo` or as root.

### Listing Installed Packages (list)

To list all the installed packages, you can use the `package list` command, this command will list all the installed
packages and their versions.

```shell
$ ncc package list
   com.symfony.console=2.0.7
   com.symfony.polyfill_php72=v1.28.0
   com.symfony.http_kernel=2.0.7
   com.symfony.event_dispatcher=2.0.7
Total: 4 packages
```

### Installing Packages (install)

To install a package, you can use the `package install` command, this command will install the specified package and
all its dependencies. There are two ways to install a package, you can either install a package from a remote source
or install a package from a local source such as a local .ncc file.

| Option                | Required | Example                                                         | Description                                                                                                                                                                               |
|-----------------------|----------|-----------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `--package`, `-p`     | Yes      | `symfony/process=latest@packagist` or `com.example.package.ncc` | The package to install it can be a remote package or a local package, see [Remote Package Syntax (RPS)](#remote-package-syntax-rps) for more information when installing a remote package |
| `--version`, `-v`     | No       | `1.0.0` or `latest`                                             | The version of the package to install, defaults to `latest`                                                                                                                               |
| `--reinstall`         | No       | Not Applicable                                                  | Reinstall the package even if it's already installed                                                                                                                                      |
| `--skip-dependencies` | No       | Not Applicable                                                  | Skips installing the package's dependencies                                                                                                                                               |
| `-y`                  | No       | Not Applicable                                                  | Skips the confirmation prompt when installing a package                                                                                                                                   |

```shell
ncc package install -p symfony/process=latest@packagist -y
```

 > **Note** When installing a package from a local file such as a packge.ncc file, ncc will display information about
 > the package and ask you to confirm the installation before installing the package, but for remote packages, ncc will
 > cannot display this information without downloading the package first, so it will ask you to confirm the installation

### Uninstalling Packages (uninstall)

To uninstall a package, you can use the `package uninstall` command, this command will uninstall the specified package.
If you don't specify a version, ncc will uninstall all versions of the package.

| Option            | Required | Example                                        | Description                                                                      |
|-------------------|----------|------------------------------------------------|----------------------------------------------------------------------------------|
| `--package`, `-p` | Yes      | `com.example.package` or `com.symfony.process` | The package to uninstall                                                         |
| `--version`, `-v` | No       | `1.0.0` or `latest`                            | The version to uninstall, if not specified then all versions will be uninstalled |
| `-y`              | No       | Not Applicable                                 | Skips the confirmation prompt when uninstalling a package                        |

```shell
ncc package uninstall -p symfony/process -v 1.0.0 -y
```

### Uninstalling All Packages (uninstall-all)

To uninstall all packages, you can use the `package uninstall-all` command, this command will uninstall all the packages
that were installed using ncc. This command does not accept any options other than `-y` to skip the confirmation prompt.

```shell
ncc package uninstall-all -y
```

### Fix Broken Packages (fix-broken)

To fix broken packages, you can use the `package fix-broken` command, this command will attempt to fix or uninstall
packages that are broken. This command does not accept any options other than `-y` to skip the confirmation prompt.

The command does the following checks

 - Check each package to see if they are broken eg; missing files, or any other unrecoverable error that would
   prevent the package from being loaded correctly, these packages will be uninstalled.
 - Check each package's dependencies to see if they are installed, if not, it will try to determine all the missing
   dependencies.

```shell
ncc package fix-broken -y
```


## Credentials Management (cred)

Credentials are used in ncc to pull packages from private sources, such as private repositories on GitHub, GitLab, Gitea,
and so on. ncc uses a credential manager to manage credentials for these sources, this command provides a set of commands
for managing credentials.

All credentials stored by ncc are encrypted using the provided token/password, this means each entry in the credential manager
is encrypted and requires a secret to decrypt it, so even if the credential database can be accessed, the credentials
cannot be decrypted without the secret.

 > **Note:** The credential manager is not a password manager, it's only used to store credentials for private sources
 > such as private repositories on GitHub, GitLab, Gitea, and so on, root access is required to access and make changes
 > to the credential manager. The credential database is only accessible by root.

### Adding a credential (add)

To add a credential, you can use the `cred add` command, this command will add a new credential to the credential manager,
this command will prompt you to enter the credential details such as the credential name, the credential type, and the
credential token/password, if you provide the options needed for these details, you can skip the prompts by using the
options instead.

| Option        | Required       | Example          | Description                                                                                              |
|---------------|----------------|------------------|----------------------------------------------------------------------------------------------------------|
| `--alias`     | Yes            | `johndoe`        | The alias of the credential                                                                              |
| `--auth-type` | Yes            | `login` or `pat` | The type of the credential, can either be `login` (Username & Password) or `pat` (Personal Access Token) |
| `--username`  | Yes if `login` | `johndoe`        | The username of the credential, only required if the credential type is `login`                          |
| `--password`  | Yes if `login` | `password`       | The password of the credential, only required if the credential type is `login`                          |
| `--token`     | Yes if `pat`   | `token`          | The token of the credential, only required if the credential type is `pat`                               |

```shell
ncc cred add --alias johndoe --auth-type login --username johndoe --password <password>
ncc cred add --alias secretdoe --auth-type pat --token <token>
```

### Removing a credential (remove)

To remove a credential, you can use the `cred remove` command, this command will remove a credential from the credential
manager, this command requires the '--alias' option to specify the credential to remove.

| Option    | Required | Example   | Description           |
|-----------|----------|-----------|-----------------------|
| `--alias` | Yes      | `johndoe` | The alias of the user |

```shell
ncc cred remove --alias johndoe
```


### Listing credential entries (list)

To list all the credential entries, you can use the `cred list` command, this command will list all the credential entries
in the credential manager.

```shell
$ ncc cred list
Entries:
 - johndoe  (encrypted)
 - secretdoe  (encrypted)
Total: 2
```


## Managing Repositories (repository or repo)

Repositories are used in ncc to pull packages from remote sources, such as GitHub, GitLab, Gitea, and so on. ncc uses
a repository manager to manage repositories for these sources, this command provides a set of commands for managing
repositories.

Note that ncc currently supports the following repository types:

 - GitHub
 - GitLab
 - Gitea
 - Packagist

And by default, ncc comes with the following repositories:

| Name       | Type      | Host                                               | Description                                                                                        |
|------------|-----------|----------------------------------------------------|----------------------------------------------------------------------------------------------------|
| n64        | gitlab    | [git.n64.cc](https://git.n64.cc)                   | The official n64 git instance, maintained by Nosial & a community of volunteers                    |
| packagist  | packagist | [packagist.org](https://packagist.org)             | The official Packagist repository, mainly used for Composer packages                               |
| github     | github    | [github.com](https://github.com)                   | GitHub's official Git instance                                                                     |
| gitlab     | gitlab    | [gitlab.com](https://gitlab.com)                   | GitLab's official Git instance                                                                     |
| gitgud     | gitlab    | [gitgud.io](https://gitgud.io)                     | Gitgud's public GitLab instance, maintained by a community of volunteers                           |
| nocturn9x  | gitea     | [git.nocturn9x.space](https://git.nocturn9x.space) | Nocturn9x's private Gitea instance, maintained by a community of volunteers from the private cloud |
| martinvlba | gitea     | [git.martinvlba.eu](https://git.martinvlba.eu)     | Martinvlba's public Gitea instance, maintained by the site owner                                   |
| kuny       | gitea     | [git.it-kuny.ch](https://git.it-kuny.ch)           | Kuny's public Gitea instance, maintained by the site owner                                         |

If you'd like to add your own repository to ncc as a default repository, you can submit a pull request to add it to
the [repositories.json](src/config/default_repositories.json) file, this file contains all the default repositories
that ncc will attempt to add when first installing ncc.


### Adding a repository (add)

To add a repository to ncc, you can use the `repo add` command, this command will add a new repository to the repository
manager, this command requires you to provide the repository name, the repository type, and the repository host.

| Option   | Required | Example           | Description                                                                                                                |
|----------|----------|-------------------|----------------------------------------------------------------------------------------------------------------------------|
| `--name` | Yes      | `n64`             | The name of the repository                                                                                                 |
| `--type` | Yes      | `gitlab`          | The type of the repository, can either be `gitlab`, `github`, `gitea`, or `packagist`                                      |
| `--host` | Yes      | `git.n64.cc`      | The host of the repository, this is the URL to the repository eg; `git.n64.cc` for the n64 repository (Without http/https) |
| `--ssl`  | No       | `true` or `false` | Whether or not to use SSL when connecting to the repository, defaults to `true`                                            |

```shell
ncc repo add --name n64 --type gitlab --host git.n64.cc
```

### Removing a repository (remove)

To remove a repository, you can use the `repo remove` command, this command will remove a repository from the repository
manager, this command requires the '--name' option to specify the repository to remove.

| Option   | Required | Example | Description      |
|----------|----------|---------|------------------|
| `--name` | Yes      | `n64`   | The name of repo |

```shell
ncc repo remove --name n64
```

### Listing repositories (list)

To list all the repositories, you can use the `repo list` command, this command will list all the repositories in the
repository manager.

```shell
$ ncc repo list
 - n64 (git.n64.cc) [gitlab]
 - packagist (packagist.org) [packagist]
 - github (api.github.com) [github]
 - gitlab (gitlab.com) [gitlab]
 - gitgud (gitgud.io) [gitlab]
 - nocturn9x (git.nocturn9x.space) [gitea]
 - martinvlba (git.martinvlba.eu) [gitea]
 - kuny (git.it-kuny.ch) [gitea]
Total: 8
```

## Building Projects (build)

The build command is responsible for building projects, this command will build the project in the current working
directory or the directory specified by the `--path` option.

| Option           | Required | Example        | Description                                                                                                                   |
|------------------|----------|----------------|-------------------------------------------------------------------------------------------------------------------------------|
| `--path`, `-p`   | No       | ExampleProject | The directory to create/use to build the project in, if not provided then the current working directory would be used instead |
| `--config`, `-c` | No       | `release`      | The build configuration file to use, if not provided then the default build configuration will be used instead                |
| `--output`, `-o` | No       | `program`      | The output file to produce, if not provided then the default output in your build configuration will be used instead          |

```shell
ncc build
```

```shell
ncc build --config release_executable --output program
```

## Execute (exec)

The execute command is responsible for executing packages, this command can execute packages that are installed on your
system, or you can specify a package to execute from directly.

 > **Note:** Depending on if the package is statically built, you need to ensure that you have the required dependencies
 > installed on your system, if you don't have the required dependencies installed, the package will fail to execute.

| Option           | Required | Example                                                                           | Description                                                                                                                                     |
|------------------|----------|-----------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------|
| `--package`      | Yes      | `com.example.program` (From the system) or `com.example.program.ncc` (Local file) | The package to execute, this can either be a package installed on your system or a local package file                                           |
| `--exec-version` | No       | `1.0.0` or `latest`                                                               | The version of the package to execute if the package is installed on your system, defaults to `latest`                                          |
| `--exec-args`    | No       | `--arg1 --arg2`                                                                   | The arguments to pass to the package when executing it, this has to be the last option in the command before the options to pass to the package |

```shell
ncc exec --package com.example.program --exec-version latest --exec-args --arg1 --arg2
```

```shell
ncc exec --package com.example.program.ncc --exec-args --arg1 --arg2
```

The exit code of the package will be returned as the exit code of the command, if the package fails to execute, the
command will return an exit code of 1 and display the error details.