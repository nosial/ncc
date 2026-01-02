# Nosial Code Compiler

Nosial Code Compiler (ncc) is a program written in PHP that assists with the process of compiling PHP projects into
self-contained packages, ncc designed to replace composer as a package manager while offering additional runtime
capabilities to improve the development process of php projects.

## Table of contents

<!-- TOC -->
* [Nosial Code Compiler](#nosial-code-compiler)
  * [Table of contents](#table-of-contents)
  * [Introduction](#introduction)
  * [ncc packages](#ncc-packages)
    * [Difference between ncc and phar archives](#difference-between-ncc-and-phar-archives)
    * [Package Naming Convention](#package-naming-convention)
    * [Binary Structure](#binary-structure)
      * [A0 - Start Package](#a0---start-package)
      * [A1 - Package Version](#a1---package-version)
      * [A2 - Header](#a2---header)
      * [A3 - Assembly Information](#a3---assembly-information)
      * [A4 - Execution Units](#a4---execution-units)
      * [A5 - Components](#a5---components)
      * [A6 - Resources](#a6---resources)
      * [Compression](#compression)
    * [Reading compiled ncc packages](#reading-compiled-ncc-packages)
    * [Filesystem Permissions](#filesystem-permissions)
  * [Building ncc](#building-ncc)
    * [Producing a phar build](#producing-a-phar-build)
    * [Producing a regular build](#producing-a-regular-build)
    * [Preparing dependencies](#preparing-dependencies)
  * [Installing ncc](#installing-ncc)
  * [Runtime Environment](#runtime-environment)
    * [Modes](#modes)
    * [Stream wrapper and autoloading](#stream-wrapper-and-autoloading)
    * [Importing packages](#importing-packages)
      * [Signature](#signature)
      * [Behavior](#behavior)
      * [Version selection](#version-selection)
      * [Examples](#examples)
  * [Build Types](#build-types)
    * [ncc](#ncc)
    * [php](#php)
    * [phar](#phar)
  * [Project Configuration](#project-configuration)
    * [Project Conversion](#project-conversion)
    * [Creating a project from scratch](#creating-a-project-from-scratch)
    * [Macro Variables](#macro-variables)
    * [Project Structure (root)](#project-structure-root)
    * [PackageSource Object](#packagesource-object)
    * [RepositoryConfiguration Object](#repositoryconfiguration-object)
    * [Assembly Object](#assembly-object)
    * [ExecutionUnit Object](#executionunit-object)
    * [BuildConfiguration Object](#buildconfiguration-object)
  * [Building Projects](#building-projects)
  * [Repository Manager](#repository-manager)
    * [Adding a repository](#adding-a-repository)
    * [Deleting a repository](#deleting-a-repository)
    * [Listing repositories](#listing-repositories)
  * [Package Manager](#package-manager)
    * [Remote Package Format](#remote-package-format)
    * [Installing a package](#installing-a-package)
    * [Uninstalling a package](#uninstalling-a-package)
    * [Listing installed packages](#listing-installed-packages)
    * [Updating packages](#updating-packages)
<!-- TOC -->

## Introduction

`ncc` is both a runtime extension for php and a command-line tool, upon installing ncc the `ncc` runtime environment
is made available to your PHP environment simply by using the `require 'ncc';` statement, additional global functions are
provided to allow you to import packages from ncc's package manager or directly from a ncc package file.

## ncc packages

ncc provides its own package format that can only be parsed by ncc, php does not support ncc packages natively but ncc
projects has the ability to produce a self-contained statically built phar output if needed. This part of the
documentation will explain what a ncc package is, how the internal structures work and how it's intended to be used and
it's differences between a standard php `phar` archive file.


### Difference between ncc and phar archives

ncc package files are designed to be compiled based off a ncc project configuration, a ncc project configuration dictates
properties and options of how a php project should be compiled just like how composer `composer.json` files operatate.
The major difference is how the files are read in-memory, to avoid execessive memory usage or unnecessary CPU cycles being
wasted in parsing the package, ncc packages are lazily-loaded which means only parts of the files are read initially and
everything else that's needed afterward are accessed when needed.

Unlike phar files, phar files are loaded entirely into memory which causes a very noticable delay when atttempting to
execute or include a phar file into the PHP during runtime. By eliminating the "zip" arcihve structure and simply compressing
the package contents if necessary. We access contents of the package during runtime only when needed to reduce the need to
parse the entire package, especially for cases where a project may be big and complex.


### Package Naming Convention

Package names serve as a unique identifier for packages within ncc's package manager and runtime environment, in ncc
they follow a reverse domain name notation similar to Java package naming conventions. This format helps ensure
uniqueness and clarity in package identification. A package name consists of multiple segments separated by dots (`.`),
where each segment represents a level in the namespace hierarchy. For example:

- `com.example.library`: A package named "library" under the "example" domain owned by "com".
- `org.opensource.project.module`: A package named "module" under the "project" owned by "opensource" under the "org"

For compatibility with existing packages such as composer packages, ncc converts hyphenated package names into dot
notation by replacing hyphens (`-`) with underscores (`_`). This allows for seamless integration of packages from other
ecosystems while adhering to ncc's naming conventions. For example:

- `monolog/monolog` becomes `com.monolog.monolog`
- `league/oauth2-client` becomes `com.league.oauth2_client`

### Binary Structure

The ncc package structure is a simple yet effective rules of basic structure of a binary package, essentially a ncc
package consits of multiple sections seperated by clear terminators. In this part of the documentation we're going to
refer to most of the parts of the internal package structure in a hex representation for simplicity 

Package structures are sperated by the hex value `A*`"starting from 0 until `9`, if `9` is to be exceeded, the next
letter in the alphabet is used, currently in this implementation we have not gone as far as that to have to use another
differance in it's hex values.

 - `A0` - Start Package
 - `A1` - Package Version
 - `A2` - Header
 - `A3` - Assembly Information
 - `A4` - Execution Units
 - `A5` - Components
 - `A6` - Resources

Each section contains ends in a termination byte which is `E0 E0` or `E1` for soft termination, the diffence between
the two is normal termination (`EO EO`) means the entire section is ended, which is one of the `A*` sections above,
`E1` means part of that data section is terminated, which indicates additional entries of the same section exists, this
only applies to sections such as "Execution Units", "Components" or "Resources" where more than one data entry may exist.

An ncc package's entry will always begin with `A0` followed by `4E 43 43 50 48 47` which essentially becomes "NCCPKG"
in ASCII characters, this section is immediately followed by `A1` which is the package structure version to indicate
if this is a differnet version of the package structure that the package reader must account for, the ASCII string
followed by the `A1` byte is a 4-character string indicating the version number of the package structure starting from
version `1000`, future iterations will increment this ASCII number to represnet that the package includes in-compatible
changes with the previous version.

If an ncc package is embedded into another file type while retaining the exact bytes of the ncc package structure, the
package reader will be able to locate the `A0` section and read the package normally as long as the bytes are not altered
or corrupted. This is useful in cases where a ncc package may be embedded in an executable package or even a regular php
file where __halt_compiler()__ is used to embed the ncc package at the end of the php file.

#### A0 - Start Package

The `A0` section marks the beginning of the ncc package. It contains a fixed ASCII signature that identifies the file
as a ncc package. This dictates the start of the package structure. The following bytes represent the signature,

![a0](assets/a0.png "[A0][4E 43 43 50 48 47]")

When the package reader encounters the `A0` byte (`\xA0`), it reads the next 6 bytes and verifies that they match the
ASCII string "NCCPKG". If the signature does not match, the reader will throw an error indicating that the file is not
a valid ncc package.


#### A1 - Package Version

The `A1` section contains the package structure version number as a 4-character ASCII string. This version number is
separate from the project version and specifically identifies which version of the binary package format is being used.

**Binary Structure:**

![a1](assets/a1.png "[A1][31 30 30 30]")

**Parsing:**
When the package reader encounters the `A1` byte (`\xA1`), it reads the next 4 bytes as an ASCII string representing
the version number. The version starts at is `1000`. This allows ncc to support future package format changes while
maintaining backwards compatibility or providing clear error messages when encountering incompatible formats.

#### A2 - Header

The `A2` section contains the package header, which stores metadata and configuration information about the package. The
header is serialized using MessagePack format for efficient binary encoding. This part of the package is never compressed
even if the package contents are compressed so that the reader can always access the header information and determine if
the rest of the package is compressed.

**Binary Structure:**

![a2](assets/a2.png "[A2][SIZE][E1][MSGPACK_DATA][E0 E0]")


**Parsing:**
1. The reader encounters the `A2` byte (`\xA2`)
2. Reads the size prefix (platform-dependent long integer in little-endian)
3. Expects a soft terminator (`E1` / `\xE1`)
4. Reads the MessagePack-encoded header data
5. Expects a full terminator (`E0 E0` / `\xE0\xE0`) to end the section

**Header Contents:**
The header contains:
- **flags**: Array of package flags/features
- **build_number**: Unique 8-byte identifier for this build
- **statically_linked**: Boolean indicating if dependencies are embedded
- **entry_point**: Name of the main execution unit to run
- **web_entry_point**: Entry point for web/HTTP requests
- **pre_install**: Execution unit(s) to run before installation
- **post_install**: Execution unit(s) to run after installation
- **defined_constants**: Key-value pairs of constants to define at runtime
- **dependency_references**: Array of package dependencies
- **update_source**: Source location for package updates
- **repositories**: Array of repository configurations
- **autoloader**: Class-to-file mapping for PHP autoloading
- **compressed**: Boolean indicating if package contents are compressed

#### A3 - Assembly Information

The `A3` section contains assembly metadata that identifies and describes the package. Like the header, it's serialized
using MessagePack format.

**Binary Structure:**

![a3](assets/a3.png "[A3][SIZE][E1][MSGPACK_DATA][E0 E0]")

**Parsing:**
1. The reader encounters the `A3` byte (`\xA3`)
2. Reads the size prefix
3. Expects a soft terminator (`E1`)
4. Reads the MessagePack-encoded assembly data
5. Expects a full terminator (`E0 E0`) to end the section

**Assembly Contents:**
The assembly section contains:
- **name**: The human-readable name of the project
- **package**: The unique package identifier (e.g., `com.example.library`)
- **version**: The semantic version number (e.g., `1.0.0`)
- **url**: Project website URL (optional)
- **license**: License identifier (e.g., `MIT`, `GPL-3.0`)
- **description**: Project description text
- **author**: Author name and contact information
- **organization**: Organization or company name
- **product**: Product name if part of a larger product line
- **copyright**: Copyright notice
- **trademark**: Trademark information

#### A4 - Execution Units

The `A4` section contains zero or more execution units. Execution units are executable components like CLI commands,
scripts, or binaries that can be invoked by name. This section supports multiple entries.

**Binary Structure:**

![a4](assets/a4.png "[A4][NAME][E1][SIZE][E1][MSGPACK_DATA][E0 E0]...[NAME][E1][SIZE][E1][MSGPACK_DATA][E0 E0]")

**Parsing:**
1. The reader encounters the `A4` byte (`\xA4`)
2. For each execution unit:
   - Reads the name string until soft terminator (`E1`)
   - Reads the size prefix
   - Expects a soft terminator (`E1`)
   - Stores the offset and size as a reference (lazy loading)
   - Skips the data and expects terminator (`E0 E0`)
3. Continues until reaching the next section marker or end of package

**Execution Unit Contents:**
Each execution unit contains:
- **name**: Unique identifier for this execution unit
- **type**: Type of executable (`php`, `web` or `system`)
- **mode**: Execution mode (`auto`, `tty` or `pty`)
- **entry**: Entry point path or command to execute
- **working_directory**: Working directory for execution
- **arguments**: Default arguments to pass
- **environment**: Environment variables to set
- **required_files**: Files that must exist for execution
- **timeout**: Maximum execution time in seconds

Execution units are lazily loaded - the reader only stores their location and size during initial parsing, and the full
data is read and deserialized from MessagePack only when specifically requested.

#### A5 - Components

The `A5` section contains the PHP source code files that make up the package. Components are the actual executable code
that gets loaded at runtime. This section supports multiple entries.

**Binary Structure:**

![a5](assets/a5.png "[A5][PATH][E1][SIZE][E1][DATA][E0 E0]...[PATH][E1][SIZE][E1][DATA][E0 E0]")

**Parsing:**
1. The reader encounters the `A5` byte (`\xA5`)
2. For each component:
   - Reads the path/name string until soft terminator (`E1`)
   - Reads the size prefix
   - Expects a soft terminator (`E1`)
   - Stores the offset and size as a reference
   - Skips the actual data and expects terminator (`E0 E0`)
3. Continues until reaching the next section marker or end of package

**Component Details:**
Components are PHP source files stored with their relative path within the package structure. The path typically follows
the package namespace structure. For example, a component for `\Library\Utils\Helper` would be stored with a
path like `com.example.library/Utils/Helper.php`.

The actual file contents may be compressed if the header's `compressed` flag is set. Components are lazily loaded to
minimize memory usage - the full file content is only read when the component is actually required by the application.

#### A6 - Resources

The `A6` section contains non-code resources like configuration files, templates, images, or any other binary data
needed by the package. This is the final section before package termination.

**Binary Structure:**

![a6](assets/a6.png "[A6][PATH][E1][SIZE][E1][DATA][E0 E0]...[PATH][E1][SIZE][E1][DATA][E0 E0]")


**Parsing:**
1. The reader encounters the `A6` byte (`\xA6`)
2. For each resource:
   - Reads the resource path string until soft terminator (`E1`)
   - Reads the size prefix
   - Expects a soft terminator (`E1`)
   - Stores the offset and size as a reference
   - Skips the data and expects terminator (`E0 E0`)
3. Continues until reaching end-of-section marker (`E0 E0`)
4. After all resources, a final terminator marks the end of the package

**Resource Details:**
Resources can be any type of file - text files, binary files, images, etc. They are stored with their full path as it
should appear when extracted or accessed. Like components, resources may be compressed if the header flag indicates
compression is enabled.

Resources are also lazily loaded for efficiency. The package reader maintains an index of resource locations and only
reads the actual data when the resource is requested by the application.

#### Compression

ncc packages support optional compression of the package contents (components and resources) to reduce file size, by
default this is enabled unless explicitly disabled by the build configuration. Compression is applied using the `gzdeflate` algorithm.

Note that only the contents of the package is compressed, the header and assembly sections are never compressed to
ensure that the package reader can always access the necessary metadata to interpret the package structure.

When reading a compressed package, the package reader will automatically decompress the contents on-the-fly when they
are accessed, ensuring seamless access to package files without requiring manual decompression steps.

### Reading compiled ncc packages

ncc provides a class called `\ncc\Classes\PackageReader` which can be constructed using the file path of a pre-compiled
ncc package, upon creating the object the class would have already initialized all the references to all the contents
within the package without needing to load the entirty of the package into memory, when certain parts of the package is
accessed, it is read and optionally decompressed using the active file stream on the fly.

This class makes all the content and properties of a package acessisable but none of the contents can be altered without
re-compiling the package, additionally the class provides a `extract(string $outputDirectory)` method which allows the
contents of the package to be extracted to a local directory with an autoloader pre-generated so that the extracted
package can immediately work out the box.

 > Reading compiled ncc packages is not natively supported by php, requiring ncc to read & access ncc packages.


### Filesystem Permissions

ncc has to store data in order to operate correctly, for example upon installation the installer will include default 
repositories to be made availbale out of the box, the repositories are often necessary for most if not all packages to
be installed correctly, as such ncc requires the ability to write this information to the filesystem, but among other
pieces of data that ncc needs to store this is how ncc decides where to store this data; there are two permission scopes
ncc can operate in, these are

 - User Scope
 - System Scope

A user scope is where ncc will store data in the current user's home directory, this is the default behavior of ncc, this
ensures that ncc can operate without requiring elevated permissions, this is ideal for most use-cases where ncc is being used in a
development environment or in scenarios where multiple users may be using the same system but do not have access to each other's data.

A system scope is where ncc will store data in a system-wide location, this often requires elevated permissions to write to these
locations, but everyone else can access the data stored in these locations, this is ideal for scenarios where ncc is being used in a
server environment or in scenarios where multiple users need to share the same ncc data.

These scopes are used to determine where data is written/read from, for instance, when running ncc under a normal user ncc will have
both the user and system scope accessiable but only the user scope can be written to, when using ncc as a system user (such as running
ncc with `sudo`), only the system scope is made accessiable, not the user scope. The idea is that when a user installs makes a change
under the system scope, the data would become available for every user on the system, and vice versa for user scopes where that data
is only available to that user.

Currently only the Package Manager, Authentication Manager and Repository Manager makes use of these permission scopes.

## Building ncc

The build process for ncc is streamlined using the provided [Makefile](Makefile) which automates the compilation and
packaging of ncc into a distributable phar archive. The Makefile is also responsible for generating the dependencies
located within source code files, this part of the documentation will explain how the Makefile works and how to use it
for building or contributing to ncc.

First when cloning the ncc repository, make sure to initialize the git submodules by running the following command:

```sh
git submodule update --init --recursive
```

Or to update the submodules to the latest version:

```sh
git submodule update --remote --force
```

The build process requires the following PHP extensions to be installed and enabled in your PHP environment:

 - PHP curl extension
 - PHP mbstring extension
 - PHP json
 - PHP zip extension
 - PHP msgpack extension
 - PHP tokenizer extension
 - PHP Phar extension
 - PHP ctype extension

The Makefile generally uses commonly available tools on most unix-like systems, other than that no further special
tools are required to build ncc, to build ncc simply run the following command in the root directory of the repository:

### Producing a phar build

To produce a phar build of ncc, simply run the following command:

```sh
make target/ncc.phar
```

This target will run through most of the steps of the build process to produce a clean build of ncc, the resulting
phar archive will be located at `target/ncc.phar` alongside with another file `target/install.sh` which is a simple
installation script that can be used to install ncc onto the system only requiring the `ncc.phar` file being in the
same directory as the installation script.


### Producing a regular build

Producing a regular build of ncc is the process of producing a working build of ncc as a set of php files without
packaging it into a phar archive, this is useful for testing/development purposes where a working build of ncc is needed.

To produce a regular build of ncc, simply run the following command:

```sh
make target
```

This process also generates an autoloader, to require ncc in this build mode, you would use the following statement:

```php
<?php
    require 'target/build/ncc.php';
```


### Preparing dependencies

While the build process automatically prepares dependencies when building the phar archive, you may also prepare the
dependencies manually by running the following command:

```sh
make clean dependencies
```

This process simply copies all of the required PHP dependencies from the `dependencies/` directory where the git
submodules are located to the `src/ncc/Libraries/` while refactoring the namespaces to match the ncc internal structure
as to not conflict with any other packages that may be using the same dependencies during the runtime.


## Installing ncc

ncc can be installed using the provided installation script located at `target/install.sh` which is produced during
the phar build process, to install ncc simply ensure that the script and the `ncc.phar` file are in the same
and the script has executable permissions, then simply run the script with elevated permissions to install ncc in
the current system

Once the installation is complete, ncc can be accessed globally using the `ncc` command in the terminal, or within the
php environment using the `require 'ncc';` statement.

## Runtime Environment

ncc operates in two primary modes: Command-Line Interface (CLI) and Runtime. The CLI mode is intended for building,
installing and managing packages and is accessed by executing the built phar archive with the `--ncc-cli` argument.
When `require 'ncc';` is used in a PHP script, ncc initializes as a Runtime that exposes APIs to the running PHP process.

### Modes

- CLI: Use the phar build and the `--ncc-cli` flag to access commands for building, installing and managing packages.
- Runtime: Use `require 'ncc';` to enable runtime features such as autoloading, the `ncc://` stream wrapper and the `import` helper.

### Stream wrapper and autoloading

When initialized, the runtime registers the `ncc://` stream wrapper. Any package imported into the runtime becomes
accessible through that wrapper (for example, `ncc://com.example.package/`). Importing a package also registers its
autoloader so classes inside the package can be used via normal PHP `use`/`class` constructs. Package contents are
accessed lazily — file data is read only when required — which keeps memory usage low.

### Importing packages

Importing a package is done via the global helper `import`, an alias for `\ncc\Runtime::import`. The import API is
flexible and designed to cover common use cases while enforcing package integrity.

#### Signature

  - `import($package, $version = null)`
  - `$package`: `string` \| `\ncc\Classes\PackageReader`
  - `$version`: `string` (optional; used only when resolving through the Package Manager)

#### Behavior

- If `$package` is a path to a pre-compiled ncc file on disk (for example, `build/com.example.foo.ncc`), ncc opens that
  file directly with a `PackageReader`.
- If `$package` is a package name (for example, `com.example.foo`), ncc resolves it via the Package Manager and opens
  the selected installed package.
- Importing a package also causes its declared dependencies to be imported. Missing dependencies will trigger an
  exception unless the package is statically built (dependencies embedded).
- Multiple versions of a package can be installed side-by-side, but only one version may be imported into the runtime.
  Once imported, a package remains loaded for the life of the process.

#### Version selection

- Passing a specific version (for example, `1.0.0`) instructs the Package Manager to locate that exact or a satisfying version.
- Passing `latest` selects the newest available version.
- The `$version` parameter applies only to package-manager imports, not to local file imports.

#### Examples

```php
<?php
require 'ncc';
import('com.example.foo');                // Resolve and import latest installed version
import('com.example.foo', '1.0.0');       // Import a specific version
\ncc\Runtime::import('build/com.example.foo.ncc'); // Import directly from a local package file
```

## Build Types

ncc supports multiple build types with's codebase architecture designed to be extensible for future build types. The
default build type is `ncc`, which produces standard ncc packages. Additional build types can be implemented as
plugins that extend the build system.

By default, the following options can be used in the build configuration's `options` property:

 - `static` (boolean): If set to true, all dependencies will be embedded into the package during the build process,
                       resulting in a self-contained package that does not require external dependencies at runtime.

### ncc

The `ncc` build type produces standard ncc packages as described in the [ncc package format](#ncc-packages). This
build type is the default and is used when no specific build type is specified in the project configuration.

This build configuration supports the following options:

 - `compression` (boolean): If set to true, the package contents (components and resources) will be compressed
                            using the `gzdeflate` algorithm to reduce file size. Default is true.
 - `compression_level` (integer): Specifies the compression level (0-9) to use when compressing package contents.
                                  Higher levels result in better compression but slightly slower build times.
                                  Default is 9.

### php

The `php` build type is a similar approach to what the `phar` build type would do, but instead of producing a phar
archive, it produces a .php file that contains executable php code as the header of the file followed by the raw bytes
of a ncc package, this allows the produced file to be executed directly by the php interpreter while still being a valid
ncc package that can be read by ncc's package reader. However, reading or using this file requires ncc to be installed.

The `php` build extends the `ncc` build so it inherits all the options from the `ncc` build type.


### phar

The `phar` build type produces a self-contained PHP Archive (phar) file that can be executed directly by the PHP
interpreter. This build type is useful for distributing built projects as a standard phar archive that can be
understood by PHP.

 > Note: To create a truly self-contained phar build of a project that doesn't require ncc to be installed, consider
         using the `static` option in the build configuration to embed all dependencies into the phar archive.

The following options are supported in the `phar` build configuration:

 - `compression` (boolean): If set to true, the phar contents will be compressed using
                            the `Phar::GZ` compression algorithm to reduce file size. Default is true.
 - `compression_level` (integer): Specifies the compression level (0-9) to use when compressing phar contents.
                                  Higher levels result in better compression but slightly slower build times.
                                  Default is 9.
 - `skip_execution`: Skips the inclusion of the self-executable logic in the phar file, resulting in a standard phar
                     archive that cannot be executed directly by PHP. Default is false but can be imported as a standard
                     phar archive.


## Project Configuration

PHP Projects must define a project configuration file that ncc can recognize and use to build the project, the project
configuration file can be used to define everything about the project such as it's metadata, dependency requirements,
build configurations and so forth.


### Project Conversion

to ensure compatibility ncc's command-line comes with a project conversion tool that can be used to generate a ncc
project configuration based off the detected and supported project type, currently only two other project types are
supported for the conversion process:

 - ncc v2 (project.json)
 - composer (composer.json)

Using the `ncc project convert` command while in the current directory of the project will automatically attempt to
detect and locate a project configuration file it can convert. Upon success, you will see a generated `project.yml`
file in the same directory as the detected project configuration file. This generated file should work out the box
unless there were unexpected/malformed properties in your existing project configuration.

### Creating a project from scratch

You can generate a basic project boilerplate with a configuration file provided by using the command `ncc project create`
which will generate a directory for your project.

 - `--name`: The name of the project, for example `MyLibrary`
 - `--package`: The standard package name for the project, for example `com.example.foo`
 - `--path`: Optional. The root directory of your project (not the source directory), otherwise it will default to the
             current working directory.

Once the command is executed, a directory will be created under the value of `--name` (if `--path` was not used) and
within that directory you will see a `src/` directory and a `project.yml` file which would look like this:

```yml
source: src
default_build: release
assembly:
  name: MyLibrary
  package: com.example.foo
  version: 0.1.0
build_configurations:
  -
    name: debug
    output: 'target/debug/${ASSEMBLY.PACKAGE}.ncc'
    type: ncc
    definitions:
      NCC_DEBUG: true
  -
    name: release
    output: 'target/release/${ASSEMBLY.PACKAGE}.ncc'
    type: ncc
```

### Macro Variables

ncc supports the use of macro variables in certain string properties within the project configuration file. These
macros allow for dynamic value substitution based on the project's assembly metadata. The following macros are supported:

| Macro                            | Description                                               |
|----------------------------------|-----------------------------------------------------------|
| `${d}`                           | Day of the month, 2 digits with leading zeros (01–31)     |
| `${D}`                           | Short textual day of the week (Mon–Sun)                   |
| `${j}`                           | Day of the month without leading zeros (1–31)             |
| `${l}`                           | Full textual day of the week (Sunday–Saturday)            |
| `${N}`                           | ISO 8601 numeric day of week (1 = Monday … 7 = Sunday)    |
| `${S}`                           | English ordinal suffix for day (`st`, `nd`, `rd`, `th`)   |
| `${w}`                           | Numeric day of week (0 = Sunday … 6 = Saturday)           |
| `${z}`                           | Day of the year (starting from 0)                         |
| `${W}`                           | ISO 8601 week number of year                              |
| `${F}`                           | Full month name (January–December)                        |
| `${m}`                           | Month with leading zeros (01–12)                          |
| `${M}`                           | Short month name (Jan–Dec)                                |
| `${n}`                           | Month without leading zeros (1–12)                        |
| `${t}`                           | Number of days in the given month (28–31)                 |
| `${L}`                           | Leap year flag (1 = leap year, 0 = otherwise)             |
| `${o}`                           | ISO week-numbering year (may differ from `${Y}`)          |
| `${Y}`                           | Full numeric year (4+ digits)                             |
| `${y}`                           | Two-digit year                                            |
| `${a}`                           | Ante/post meridiem lowercase (`am`/`pm`)                  |
| `${A}`                           | Ante/post meridiem uppercase (`AM`/`PM`)                  |
| `${B}`                           | Swatch Internet time (000–999)                            |
| `${g}`                           | 12-hour format without leading zeros (1–12)               |
| `${G}`                           | 24-hour format without leading zeros (0–23)               |
| `${h}`                           | 12-hour format with leading zeros (01–12)                 |
| `${H}`                           | 24-hour format with leading zeros (00–23)                 |
| `${i}`                           | Minutes with leading zeros (00–59)                        |
| `${s}`                           | Seconds with leading zeros (00–59)                        |
| `${c}`                           | ISO 8601 datetime (e.g., 2004-02-12T15:19:21)             |
| `${r}`                           | RFC 2822 formatted date (e.g., Thu, 21 Dec 2000 16:01:07) |
| `${u}`                           | Unix timestamp (seconds since Jan 1 1970)                 |
| `${CWD}`                         | Current working directory                                 |
| `${PID}`                         | Process ID                                                |
| `${UID}`                         | User ID                                                   |
| `${GID}`                         | Group ID                                                  |
| `${HOME}`                        | User home path                                            |
| `${PROJECT_PATH}`                | Project root path                                         |
| `${DEFAULT_BUILD_CONFIGURATION}` | Default build configuration name                          |
| `${SOURCE_PATH}`                 | Source directory path                                     |
| `${ASSEMBLY.NAME}`               | Assembly name                                             |
| `${ASSEMBLY.PACKAGE}`            | Assembly package identifier                               |
| `${ASSEMBLY.VERSION}`            | Assembly version                                          |
| `${ASSEMBLY.URL}`                | Assembly URL                                              |
| `${ASSEMBLY.LICENSE}`            | Assembly license                                          |
| `${ASSEMBLY.DESCRIPTION}`        | Assembly description                                      |
| `${ASSEMBLY.AUTHOR}`             | Assembly author                                           |
| `${ASSEMBLY.ORGANIZATION}`       | Assembly organization                                     |
| `${ASSEMBLY.PRODUCT}`            | Assembly product                                          |
| `${ASSEMBLY.COPYRIGHT}`          | Assembly copyright                                        |
| `${ASSEMBLY.TRADEMARK}`          | Assembly trademark                                        |
| `${COMPILE_TIMESTAMP}`           | Compilation timestamp                                     |
| `${NCC_BUILD_VERSION}`           | ncc build version identifier                              |
| `${BUILD_OUTPUT_PATH}`           | Build output path                                         |


### Project Structure (root)

The base of the project configuration structure implements the following properties

| Property Name          | Property Type                                          | Description                                                                                                                                                                                    | Required |
|------------------------|--------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| `source`               | `string`                                               | The relative path of the source directory, eg; `src/`                                                                                                                                          | **Yes**  |
| `default_build`        | `string`                                               | The default build configuratoin to use, eg `release`                                                                                                                                           | **Yes**  |
| `entry_point`          | `string`                                               | The main CLI execution entrypoint for the package, this value would be the name of the execution unit                                                                                          | No       |
| `web_entry_point`      | `string`                                               | The main web execution entrypoint for the package, this value would be the name of the execution unit                                                                                          | No       |
| `update_source`        | `PackageSource(string)[]`                              | The update source for the package                                                                                                                                                              | No       |
| `pre_compile`          | `string`, `string[]`                                   | One or more execution units to execute in the pre-compile stage                                                                                                                                | No       |
| `post_compile`         | `string`, `string[]`                                   | One or more execution units to execute in the post-compile stage                                                                                                                               | No       |
| `pre_install`          | `string`, `string[]`                                   | One or more execution units to execute in the pre-installation stage                                                                                                                           | No       |
| `post_install`         | `string`, `string[]`                                   | One or more execution units to execute in the post-installation stage                                                                                                                          | No       |
| `repository`           | `RepositoryConfiguration`, `RepositoryConfiguration[]` | One or more repositories that this package will need in order to operate correctly                                                                                                             | No       |
| `assembly`             | `Assembly`                                             | Assembly information, basically metadata about the package                                                                                                                                     | **Yes**  |
| `dependencies`         | `string[]`                                             | An array of key-value dependencies such as `com.symfony.console` => `symfony/console=version@packagist` where the key is the package name and the value is the remote source of the dependency | No       |
| `execution_units`      | `ExecutionUnit[]`                                      | An array of execution units defined in the project                                                                                                                                             | No       |
| `build_configurations` | `BuildConfiguration[]`                                 | An array of build executions for ncc to use when compiling the project, at least one must be defined                                                                                           | **Yes**  |

For dependencies, each dependency must be defined using the package name as the key and the remote source as the value, for example:

```yml
dependencies:
  'com.symfony.console': 'symfony/console=^5.0@packagist'
  'com.guzzlehttp.guzzle': 'guzzlehttp/guzzle=^7.0@packagist'
```

This would define two dependencies for the project, the first dependency is `com.symfony.console` which comes from the remote source
`symfony/console` with a version constraint of `^5.0

### PackageSource Object

The package source object is basically a syntax that defines where a package can be obtained from a remote source, for
example `symfony/console=1.0.0@packagist` means the package `console` comes from the provider `symfony` under the
repository `packagist` with the version `1.0.0`

In the project configuration it's defined using tha that syntax as the update source, if you are using a repository that
is not included in ncc by default, you will need to define the repository under the `repository` section where one or more
repository can be defined. During installation these repositories are added unless the `--skip-repositories` option is
in the command-line utility

### RepositoryConfiguration Object

The repository configuration object is used to define a repository configuration to add to the ncc's repository manager
when the package is installed, these repositories are only added if a repository with the smae name does not already
exist.

| Property Name | Property Type | Description                                                                    | Required |
|---------------|---------------|--------------------------------------------------------------------------------|----------|
| `name`        | `string`      | The unique name of the repository                                              | Yes      |
| `type`        | `string`      | The repository type, must be one of `github`, `gitlab`, `gitea` or `packagist` | Yes      |
| `host`        | `string`      | The host of the repository such as `api.github.com` or `gitlab.com`            | Yes      |
| `ssl`         | `boolean`     | If SSL is used when communicating with the repository (Default: True)          | No       |


YAML Example:

```yml
repository:
  name: 'github'
  type: 'github'
  host: 'api.github.com'
```

### Assembly Object

The assembly object of a project configuration is used to define metadata information about the package, this is used to
identify what the package is, who maintains it among other information.

| Property Name  | Property Type | Description                                                               | Required |
|----------------|---------------|---------------------------------------------------------------------------|----------|
| `name`         | `string`      | The name of the project (Not the same as the package name), eg; MyLibrary | Yes      |
| `package`      | `string`      | The name of the package, eg; com.example.package                          | Yes      |
| `version`      | `string`      | A standard semver version format, eg; 1.0.0                               | Yes      |
| `url`          | `string`      | The main homepage URL related to the package                              | No       |
| `license`      | `string`      | The license type related to the project, eg; MIT                          | No       |
| `description`  | `string`      | The description of the package                                            | No       |
| `author`       | `string`      | The author(s) of the package                                              | No       |
| `organization` | `string`      | The organization the maintains the package                                | No       |
| `product`      | `string`      | The product the package is part of                                        | No       |
| `copyright`    | `string`      | Copyright for the package                                                 | No       |
| `trademark`    | `string`      | Trademark associated with the package                                     | No       |


YAML Example:

```yml
assembly:
  name: 'MyLibrary'
  package: 'com.example.mylibrary'
  version: '1.0.0'
  url: 'https://example.com/mylibrary'
  license: 'MIT'
  description: 'A sample library for demonstration purposes.'
  author: 'John Doe <johndoe@example.com>'
  organization: 'Example Corp'
  product: 'Example Suite'
  copyright: 'Copyright © 1969-2030 Example Corp. All rights reserved.'
  trademark: 'Example™'
```


### ExecutionUnit Object

The execution unit object is used to define an executable component of the project, these components can be CLI commands,
web scripts or even binary executables that are part of the project.

| Property Name       | Property Type | Description                                                                                                                         | Required |
|---------------------|---------------|-------------------------------------------------------------------------------------------------------------------------------------|----------|
| `name`              | `string`      | The unique name of the execution unit                                                                                               | Yes      |
| `type`              | `string`      | The type of execution unit, must be one of `php`, `web`, or `system`                                                                | Yes      |
| `mode`              | `string`      | The execution mode, must be one of `auto`, `tty`, or `pty`, defualt is `auto`                                                       | No       |
| `entry`             | `string`      | The entry point of the execution unit, eg; `src/cli.php`, in cases for a `system` type this would be the command/program to execute | Yes      |
| `working_directory` | `string`      | The working directory for the execution unit                                                                                        |
| `arguments`         | `string[]`    | An array of default arguments to pass to the execution unit                                                                         | No       |
| `environment`       | `key-value`   | Key-value pairs of environment variables to set when executing the unit                                                             | No       |
| `required_files`    | `string[]`    | An array of files that must exist for the execution unit to run                                                                     | No       |
| `timeout`           | `integer`     | The maximum execution time in seconds, default is no timeout (0)                                                                    | No       |

YAML Example:

```yml
execution_units:
  -
    name: 'cli'
    type: 'php'
    mode: 'tty'
    entry: 'src/cli.php'
    working_directory: 'src'
    arguments:
      - '--verbose'
    environment:
      APP_ENV: 'production'
    required_files:
      - 'src/config.php'
    timeout: 60
```


### BuildConfiguration Object

The build configuration object is used to define a build configuration for ncc to use when compiling the project, the
default build configuration is defined in the `default_build` property of the project configuration. But when building
the package, you can use the `--configuration`, `-c` option to specify which build configuration to use.

| Property Name        | Property Type | Description                                                                                                                                                                                    | Required |
|----------------------|---------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------|
| `name`               | `string`      | The unique name of the build configuration                                                                                                                                                     | Yes      |
| `output`             | `string`      | The output path of the compiled package, supports macro variables such as `${ASSEMBLY.PACKAGE}`                                                                                                | Yes      |
| `type`               | `string`      | The type of build, the supported types are `ncc`, `phar` and `php`                                                                                                                             | Yes      |
| `definitions`        | `key-value`   | Key-value pairs of definitions to set when the package is imported, these use the builtin `define` method                                                                                      | No       |
| `include_components` | `string[]`    | An array of component glob patterns to include in the build                                                                                                                                    | No       |
| `exclude_components` | `string[]`    | An array of component glob patterns to exclude from the build                                                                                                                                  | No       |
| `include_resources`  | `string[]`    | An array of resource glob patterns to include in the build                                                                                                                                     | No       |
| `exclude_resources`  | `string[]`    | An array of resource glob patterns to exclude from the build                                                                                                                                   | No       |
| `dependencies`       | `string[]`    | An array of key-value dependencies such as `com.symfony.console` => `symfony/console=version@packagist` where the key is the package name and the value is the remote source of the dependency | No       |

YAML Example:

```yaml
build_configurations:
  -
    name: 'debug'
    output: 'target/debug/${ASSEMBLY.PACKAGE}.ncc'
    type: 'ncc'
    definitions:
      NCC_DEBUG: true
    include_components:
      - 'src/**/*.php'
    exclude_components:
      - 'src/tests/**'
    include_resources:
      - 'resources/**'
    exclude_resources:
      - 'resources/temp/**'
  -
    name: 'release'
    output: 'target/release/${ASSEMBLY.PACKAGE}.ncc'
    type: 'ncc'
```

## Building Projects

the `ncc build` command is used to build ncc projects into compiled packages, the build process is based entirely off
the build configuration and project configuration defined in the project configuration file, the build process will
also resolve dependencies defined in the project configuration file using the package manager before compiling the
project.

```sh
ncc build # Builds the project using the default build configuration
ncc build --configuration debug # Builds the project using the 'debug' build configuration
ncc build -c release # Builds the project using the 'release' build configuration
```

If you are missing dependencies you can use the `ncc project install` command to install all the dependencies defined in the
project configuration file before building the project.


## Repository Manager

Repository Management in ncc provides a way for ncc to be able to find packages from different sources, by default ncc 
comes  with a set of default repositories that are pre-configured during the installation process, these repositories
are often stored as system repositories so that every user on the system can access them, however users can also add
their own repositories in their user scope if they wish to do so.

 - `packagist`: The packagist repository for retrieving most open-source php packages that are often used in php projects,
   ncc provides composer compatibility to be able to convert composer projects into ncc projects while installing
   packages from packagist and or any other source that supports composer packages.
 - `n64`: A self-hosted git repository from Nosial, the maintainers of ncc, this repository contains packages that are
   specifically built for ncc and are not available on packagist.
 - `github`: The GitHub repository source, this repository allows ncc to retrieve packages directly from GitHub repositories
 - `gitlab`: The GitLab repository source, this repository allows ncc to retrieve packages directly from GitLab repositories
 - `codeberg`: The Codeberg repository source, this repository allows ncc to retrieve packages directly from Codeberg repositories

### Adding a repository

Repositories can be added using the command-line interface of ncc, for example to add a GitHub repository you would
use the following command:

```sh
ncc repo add --name github --type github --host api.github.com
```

Currently, ncc only supports the following repository types which is basically an abstract API support layer for different
repository implementations.

- `packagist`: Packagist Repository
- `github`: GitHub Repository
- `gitlab`: GitLab Repository
- `gitea`: Gitea Repository

Additional arguments can be provided:

 - `--ssl`: Enable SSL verification (default: true)
 - `--overwrite`: Overwrite existing repository with the same name

### Deleting a repository

Repositories can be deleted using the command-line interface of ncc, for example to delete a repository you would use
the following command:

```sh
ncc repo del --name github
```

 > Note: Deleting repositories from the system scope requires elevated permissions.

### Listing repositories

Repositories can be listed using the command-line interface of ncc, for example to list all available repositories
you would use the following command:

```sh
ncc repo list
```


## Package Manager

ncc provides a package manager, packages can be installed in a System Scope or User Scope depending on the permissions
of the user running ncc, packages installed in the System Scope are available to all users on the system while packages
installed in the User Scope are only available to that user.

The package manager is only managed via the command-line interface of ncc, once packages are installed they can be imported
into the runtime environment using the `import` method.

### Remote Package Format

Remote packages are identified by ncc using a simple syntax format such as: `organization/package=version@repository`
where

 - `organization`: The name of the organization that maintains the package
 - `package`: The name of the package (Not the same as a package name such as `com.example.bar`)
 - `version`: Optional. The version of the package
 - `repository`: The repository name where the package is hosted

This the same syntax used in an ncc project configuration to define dependencies that a project may require to be built
or imported successfully.

### Installing a package

Packages can be installed either from a local pre-compiled ncc package file or from a remote repository, for example to
install a package from a remote repository you would use the following command:

```sh
ncc install --package="com.example.foo.ncc"
```

Installs the package `com.example.foo.ncc` from local disk if the file exists, when poviding a command such as:

```sh
ncc install --package="organization/package@repository"
```

Would install the latest version of the package `organization/package` from the repository named `repository`.
To install a specific version of a package from a remote repository you would use the following command:

```sh
ncc install --package="organization/package=1.0.0@repository"
```

Would install version `1.0.0` of the package `organization/package` from the repository named `repository`.

ncc is designed to be compatible with composer packages, when installing packages from repositories such as packagist,
or any other repository that contains an ncc project, ncc will automatically convert the composer package into a ncc
project during the installation process and compile it. For example installing a library such as
`symfony/process=latest@packagist` will have ncc retreive the package from the `packagist` repository, automatically
parse the project structure and attempt to use a conversion process only if applicable.


### Uninstalling a package

The `ncc uninstall` command has several different functions built into it, for example you can uninstall a specific
version of a package using:

```sh
ncc uninstall --package="com.example.package" --version="1.0.0"
```

or to uninstall all versions of a package, you omit the `--version` argument as such:

```sh
ncc uninstall --package="com.example.package"
```

To uninstall all packages that are installed, you can use the `--all` command as such:

```sh
ncc uninstall --all
```

Note that when uninstalling packages, you are only uninstalling packages you have write-access to, to uninstall packages
from the system you need to run ncc with elevated permissions so that the ncc has write-access to the system scope.


### Listing installed packages

You can list all the packages that are installed and currently available to ncc using the `ncc list` or `ncc ls` command,
this will display packages both installed in the user-scope and system-scope


### Updating packages

You can update installed packages by using the `ncc update` command, this will iterate through all the installed writeable
packages from the package manager, check for a newer version than what you have installed and install the new version
correctly.

Note, this will only work for installed packages that had defined an update source in their project configuration file.