# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - Ongoing

This update introduces a complete new rewrite of the Nosial Code Compiler with a different focus and better
maintainability of the project

### Added
 - Added the ability to build ncc in a self-contained php archive (phar)
 - Added legacy project conversion tool to convert ncc 1.* & 2.* projects to ncc 3.* format
 - Added the ability to compile `php` packages, where ncc is embedded into a file with a executable php header
 - Added the ability to compile `phar` packages

### Changed
 - Changed the ncc package structure, ncc v3+ Will no longer be compatible with ncc 1.* & 2.* package structure versions
   the project must be rebuilt
 - Refactored the CLI entirely
 - Changed the project structure format
 - Changed the project structure file format from JSON to YAML
 - Refactored Makefile to handle a more clean approach into handling dependencies and avoiding conflicts, the Makefile can
   now build the dependencies that ncc would require from the git submodules of the repository. 
 - Streamlined the command-line interface to be more simple, with commands to manage/operate different parts of ncc instead
   commands having many sub-commands instead. For instance instead of `ncc project build` it's instead `ncc build`, instead
   of `ncc package list` it's simply just `ncc list`, see the command-line help menu for more information, older commands
   and arguments will no longer work with ncc 3.0+

### Removed
 - Removed build requirements such as `phpab` and `phive`, the project can now be independently built using `pal`
   (PHP Autoloader)
 - Removed "runners" and other build tools for every other language except for PHP, for running code/commands that isn't
   PHP related, execution units has been refactored allow executing both PHP and SYSTEM commands/actions
 - Removed the internal logging in favor for `LogLib2` which can now independently be built with the project
 - Removed gitea repository `nocturn9x` git.nocturn9x.space because it's no longer maintained
 - Removed gitea repository `martinvlba` git.martinvlba.eu because it's no longer maintained
 - Removed gitea repository `kuny` git.it-kuny.ch because it's no longer maintained



## [2.1.8] - 2025-03-17

This update introduces a temporary fix

### Fixed
 - (Temporary) removed the ability to delete cache files because it's deleting important files that are needed.


## [2.1.7] - 2025-03-17

This update introduces a quality of life improvement

### Changed
 - Refactor variable names for consistency and clarity in ShutdownHandler and PackageWriter classes
 - Improve error handling in ShutdownHandler by logging warnings for cleanup failures

### Fixed
 - Fixed issue in ArchiveExctractor where close() may be called on a already closed resource


## [2.1.6] - 2024-10-29

This update introduces critical bug fixes

### Fixed
 - Validate package instance before checking execution policy



## [2.1.5] - 2024-10-14

This update introduces a critical bug fix



## [2.1.4] - 2024-10-13

This update introduces minor bug fixes & improvements

### Added
 - Added new constant `%DEFAULT_BUILD_CONFIGURATION%` which points to the project's default build configuration
 - Added new dynamic constant `%BUILD_OUTPUT_PATH%` which can be used as `%BUILD_OUTPUT_PATH%:release` to show the
   output path of a specific build configuration
 - Refactor CI templates to support dynamic build targets
 - Added template `phpexe` & `phpgz` for generating executable binaries and compressed executables for the project

### Changed
 - Refactor phpmake template to support dynamic build targets

### Fixed
 - ncc will now correctly handle package execution where the main unit is not defined in the package instead of
   throwing an exception.
 - Executable Compiler will now correctly create a directory if it does not exist when compiling a package using gcc



## [2.1.3] - 2024-09-27

This update introduces bug fixes

### Fixed
 - Null-pointer fix


## [2.1.2] - 2024-09-27

This update introduces bug fixes

### Added
 - Add getter methods for installation lifecycle steps
 - Added pre-install & post-install execution unit handling

### Changed
 - Updated DocStrings in PackageManager

### Fixed
 - Fixed incorrect enum usage in RepositoryMenu


## [2.1.1] - 2024-09-24

This update introduces a couple of new features and quality of life improvements

### Added
 - Added new PHP template `phpunit` for generating PhpUnit tests for the project
 - Added new PHP template `phpmake` for generating a Makefile for the project
 - Added new PHP template `phplib_full` That combines `phplib`, `phpmake` and `phpunit` templates
 - Added new PHP template `phpcli_full` That combines `phpcli`, `phplib`, `phpmake` and `phpunit` templates
 - Added new PHP template `phpci_github` that generates a Github CI workflow for the project

### Changed
 - The template `phpcli` no longer includes a Makefile, instead use `phpmake` to generate a Makefile for the project

### Fixed
 - Fixed incorrect enum usage with a logging method call.


## [2.1.0] - 2024-09-20

This update introduces a refactored code-base, code quality improvements, and better exception handling.

### Added
 - Added Test unit tests/ncc/Objects/Package/ComponentTest.php
 - Added Test unit tests/ncc/Utilities/ResolverTest.php
 - Added Test unit tests/ncc/Utilities/SecurityTest.php
 - Added Test unit tests/ncc/Utilities/ValidateTest.php
 - Add support for runtime constants

### Changed
 - Convert Versions constants to enum cases
 - Update Scopes to enum and adjust scope checks
 - Convert Runners class to enum with string cases
 - Convert RegexPatterns constants to enum cases
 - Convert ProjectTemplates constants to enum cases
 - Convert PackageStructureVersions constants to enum cases
 - Convert PackageStructure constants to enum cases
 - Convert PackageDirectory constants to enum cases
 - Convert LogLevel constants to enum cases
 - Convert FileDescriptor constants to enum cases
 - Convert ExceptionCodes constants to enum cases
 - Convert ConsoleColors constants to enum cases
 - Convert CompilerExtensions constants to enum cases
 - Convert RepositoryType constants to enum cases
 - Convert RepositoryResultType constants to enum cases
 - Convert ProjectType constants to enum cases
 - Convert HttpRequestType constants to enum cases
 - Convert ComposerStabilityTypes constants to enum cases
 - Convert ComposerPackageTypes constants to enum cases
 - Convert ComponentDataType constants to enum cases
 - Convert BuildOutputType constants to enum cases
 - Convert AuthenticationType constants to enum cases
 - Convert RuntimeConstants constants to enum cases
 - Convert InstallConstants constants to enum cases
 - Convert DateTimeConstants constants to enum cases
 - Convert BuildConstants constants to enum cases
 - Convert AssemblyConstants constants to enum cases
 - Convert ProjectOptions constants to enum cases
 - Convert InstallPackageOptions constants to enum cases
 - Convert InitializeProjectOptions constants to enum cases
 - Convert ComponentDecodeOptions constants to enum cases
 - Convert BuildConfigurationValues constants to enum cases
 - Convert BuildConfigurationOptions constants to enum cases
 - Convert CompressionOptions constants to enum cases
 - Convert PackageFlags constants to enum cases
 - Convert NccBuildFlags constants to enum cases
 - Convert ComponentFlags constants to enum cases
 - Refactor flag handling to use PackageFlags enum directly
 - Refactor checkLogLevel to correctly utilize LogLevel cases
 - Refactor code to improve readability in Resolver.php
 - Update PHP include paths in project configuration
 - Refactor logging level checks to use LogLevel enum directly
 - Refactor log level parsing with enum method
 - Refactor log level checking to enum method
 - Updated Symfony/Filesystem from version 6.3.1 to 7.1.2
 - Refactor ProjectType handling
 - Validate and enforce repository type enum usage
 - Updated Composer/Semver to 3.4.3
 - Rename 'semver' directory to 'Semver' in composer package
 - Refactor project constants handling in NccCompiler
 - Updated Symfony/Yaml to version 7.1.4
 - Updated Symfony/Uid to version 7.1.4
 - Updated Symfony/Process to version 7.1.3
 - Updated Symfony/polyfill-ctype to version 1.31.0
 - Updated Symfony/polyfill-mbstring to version 1.31.0
 - Updated Symfony/polyfill-uuid to version 1.31.0
 - Updated nikic/PhpParser to version 5.2.0

### Fixed
 - Fixed Division by zero in PackageManager
 - Fixed runner argument passing for ExecCommand

### Removed
 - Removed EncoderType enum file, unused.
 - Removed PackageStandardVersions.php
 - Removed ConstantReferences.php
 - Removed HttpStatusCodes.php
 - Removed CompilerExtensionDefaultVersions.php
 - Removed RemoteSourceType
 - Removed DependencySourceType
 - Removed BuiltinRemoteSourceType
 - Removed RuntimeImportOptions
 - Remove ComponentFileExtensions enum
 - Remove unused import and redundant scope validation method

## [2.0.3] - 2023-10-17

This update includes enhanced support for PHP statements in AST traversal, a friendly CLI Progress Bar, and fixes
related to package version searching, ncc's extension registration, and error handling in PackageManagerMenu.
The changes improve the system's efficiency, error resilience, and user experience.

### Added
 - Implemented support in the AST traversal for the PHP statements `include`, `include_once`, `require`, and
   `require_once`. These statements are transformed into function calls. With this change, ncc can correctly handle and 
   import files from system packages or direct binary package files.
 - Added new `ConsoleProgressBar` class for UI improvement, imrpoved the CLI Progress Bar inspired by
   [pacman](https://wiki.archlinux.org/title/pacman)

### Fixed
 - When finding package versions in the package lock, ncc will try to find a satisfying version rather than the exact
   version, this is to prevent[CHANGELOG.md](CHANGELOG.md) errors when the package lock contains a version that is not available in the repository.
 - Fixed issue when registering ncc's extension, when using the INSTALLER, the installation path used in the process
  appears to be incorrect, added a optional parameter to the `registerExtension` method to allow the installer to pass
  the correct installation path.
 - Add error handling for unspecified package in PackageManagerMenu


## [2.0.2] - 2023-10-13

In this new update, the software introduces a feature for importing static packages without extra dependencies and
supports pulling static versions of packages from repositories. Changes include enhanced security through an updated
fetchPackage method along with improvements in dependency imports and extension registration logic for Debian packages.
Finally, bugs related to unnecessary bundling of some build artifacts and mime type errors in ncc-package.xml have
been fixed.

### Added
 - Added support for importing static packages from the system, allowing you to install static packages onto your
   system and import them without importing additional dependencies that isn't already included in the package.
 - Added the ability to pull static versions of packages from repositories where package names ending with `-static.ncc`
   or `_static.ncc` can be pulled if you use the `--prefer-static` or `--static` option when using the `package install`
   command. Otherwise, the normal package will be installed, avoiding static versions.

### Changed
 - Updated fetchPackage method to include authentication
 - Update dependencies import in \ncc\Classes > Runtime > importFromPackage()
 - Refactor ncc extension registration logic, this also allows for debian packages to install the ncc extension
   automatically.

### Fixed
 - Fixed issue where some build artifacts are being bundled with package builds such as `output_path` and `static`
   where they should not be included in the package.
 - Corrected mistakes in `ncc-package.xml` mime type


## [2.0.1] - 2023-10-11

Added a new feature in `ProjectManager.php` that allows automatic installation of a project's dependencies. The 
`installDependencies` function eases the task of separately installing each dependency. Now, both public and private 
dependencies can be installed collectively using the 'install' command added in `ProjectMenu.php`. This functionality 
is further documented in `DOCUMENTATION.md`

### Added
 - Add functionality to install project dependencies with the new `project install` command in `ProjectMenu.php`

### Fixed
 - Correct exception message for configuration property in `\ncc\Objects\ProjectConfiguration\Build > BuildConfiguration > fromArray()`



## [2.0.0] - 2023-10-10

This update introduces major changes in the codebase, including the removal of Win32 support, and the addition of new
features. This is the Mk II of ncc, and it is a major update, so please read the changelog carefully.


### Known issues
 - `require` and `include` statements are not supported; when running a program directly, this feature will be added in
   a future update. Using these statements will result in files not being found.

### Added
 - `LICENSE.md` & `license.md` are now detected as license files in  `\ncc\Classes\ComposerExtension > ComposerSourceBuiltin > convertProject()`
 - Added new exception `PathNotFoundException` and implemented it in replacement for `DirectoryNotFoundException` and
   `FileNotFoundException` in `\ncc\Exceptions`
 - Added a new interface class `BytecodeObjectInterface` which will be used to implement object types for compiled assets
 - Added new exception `GitException` in `\ncc\Exceptions` to replace all git related exceptions
 - Added new exception `PackageException` in `\ncc\Exceptions` to replace all package related exceptions
 - Added new exception `OperationException` in `\ncc\Exceptions` to replace all generic related exceptions
 - Added a new interface class `SerializableObjectInterface` to implement serializable objects, `BytecodeObjectInterface`
   extends this interface to allow for serialization of compiled assets
 - Added a new interface class `ValidatableObjectInterface` to implement validatable objects, this method will throw a
   `ConfigurationException` if the object is not valid or a `NotSupportedException` if the object contains methods that
   are not supported by the current version of ncc or project.
 - Added a new interface class `TemplateInterface` to implement template classes
 - Added new template PhpCliTemplate `phpcli`
 - Added new template PhpLibraryTemplate `phplib`
 - Added the ability to clean arrays in `\ncc\Utilities > Functions > cleanArray()`
 - Added the ability to compile executable binaries for php using `gcc`
 - Added support for Gitea repositories
 - Added support for Packagist repositories
 - Added a new default gitea repository `nocturn9x` git.nocturn9x.space
 - Added a new default gitea repository `martinvlba` git.martinvlba.eu
 - Added a new default gitea repository `kuny` git.it-kuny.ch
 - Added dependency `composer/semver` version 3.4.0 for composer version comparison compatibility
 - Added new class `\ncc\Classes > ArchiveExtractor` to extract multiple archive types

### Fixed
 - Fixed MITM attack vector in `\ncc\Classes > HttpClient > prepareCurl()`
 - Fixed all @throw tags in the project to use the correct exception class
 - Fixed hash comparisons to use `hash_equals` implementations to combat against Timing Attacks

### Changed
 - Corrected code-smell and code style issues in `\ncc\Classes > HttpClient`
 - Corrected code-smell and code style issues in `\ncc\Classes > BashExtension > BashRunner` 
 - Corrected code-smell and code style issues in `\ncc\Classes > ComposerExtension > ComposerSourceBuiltin`
 - Corrected code-smell and code style issues in `\ncc\Classes > GithubExtension > GithubService`
 - Corrected code-smell and code style issues in `\ncc\Classes > GitlabExtension > GitlabService`
 - Corrected code-smell and code style issues in `\ncc\Classes > NccExtension > ConstantCompiler`
 - Renamed `Abstracts` namespace to `Enums`
 - Updated class type to "final class" in `\ncc\Enums\Options > BuildConfigurationValues`
 - Updated class type to "final class" in `\ncc\Enums\Options > InitializeProjectOptions`
 - Updated class type to "final class" in `\ncc\Enums\Options > InstallPackageOptions`
 - Updated class type to "final class" in `\ncc\Enums\SpecialConstants > AssemblyConstants`
 - Updated class type to "final class" in `\ncc\Enums\SpecialConstants > BuildConstants`
 - Updated class type to "final class" in `\ncc\Enums\SpecialConstants > DateTimeConstants`
 - Updated class type to "final class" in `\ncc\Enums\SpecialConstants > InstallConstants`
 - Updated class type to "final class" in `\ncc\Enums\SpecialConstants > RuntimeConstants`
 - Updated class type to "final class" in `\ncc\Enums > AuthenticationType`
 - Updated class type to "final class" in `\ncc\Enums > CompilerExtensionDefaultVersions`
 - Updated class type to "final class" in `\ncc\Enums > CompilerExtensions`
 - Updated class type to "final class" in `\ncc\Enums > CompilerExtensionSupportedVersions`
 - Updated class type to "final class" in `\ncc\Enums > ComponentDataType`
 - Updated class type to "final class" in `\ncc\Enums > ComponentFileExtensions`
 - Updated class type to "final class" in `\ncc\Enums > ComposerPackageTypes`
 - Updated class type to "final class" in `\ncc\Enums > ComposerStabilityTypes`
 - Updated class type to "final class" in `\ncc\Enums > EncoderType`
 - Updated class type to "final class" in `\ncc\Enums > ExceptionCodes`
 - Updated class type to "final class" in `\ncc\Enums > HttpRequestType`
 - Updated class type to "final class" in `\ncc\Enums > HttpStatusCodes`
 - Updated class type to "final class" in `\ncc\Enums > LogLevel`
 - Updated class type to "final class" in `\ncc\Enums > NccBuildFlags`
 - Updated class type to "final class" in `\ncc\Enums > PackageStandardVersions`
 - Updated class type to "final class" in `\ncc\Enums > PackageStructureVersions`
 - Updated class type to "final class" in `\ncc\Enums > ProjectType`
 - Updated class type to "final class" in `\ncc\Enums > RegexPattern`
 - Updated class type to "final class" in `\ncc\Enums > RemoteSourceType`
 - Updated class type to "final class" in `\ncc\Enums > Runners`
 - Updated class type to "final class" in `\ncc\Enums > Scopes`
 - Updated class type to "final class" in `\ncc\Enums > Versions`
 - Corrected code-smell and code style issues in `src/installer/hash_check.php`
 - Corrected code-smell and code style issues in `\ncc\Managers > ProjectManager`
 - Corrected code-smell and code style issues in `\ncc\Objects\NccVersionInformation > Component`
 - Corrected code-smell and code style issues in `\ncc\Objects\Package > Component`
 - Corrected code-smell and code style issues in `\ncc\Managers > ConfigurationManager`
 - Corrected code-smell and code style issues in `\ncc\Managers > CredentialManager`
 - Refactored `\ncc\Utilities > PathFinder` to remove all Win32 references
 - Corrected code-smell and code style issues in `\ncc\Objects > ExecutionPointers`
 - Corrected code-smell and code style issues in `\ncc\Managers > ExecutionPointerManager`
 - Corrected code-smell and code style issues in `\ncc\Utilities > Functions`
 - Corrected code-smell and code style issues in `\ncc\Managers > PackageManager`
 - Corrected code-smell and code style issues in `\ncc\Utilities > IO`
 - Corrected code-smell and code style issues in `\ncc > ncc`
 - Corrected code-smell and code style issues in `\ncc\CLI > Main`
 - Corrected code-smell and code style issues in `\ncc\Objects > Package`
 - Corrected code-smell and code style issues in `\ncc\Classes\NccExtension > PackageCompiler`
 - Corrected code-smell and code style issues in `\ncc\CLI > PackageManagerMenu`
 - Corrected code-smell and code style issues in `\ncc\Classes\PhpExtension > PhpCompiler`
 - Corrected code-smell and code style issues in `\ncc\Classes\PhpExtension > PhpInstaller`
 - Corrected code-smell and code style issues in `\ncc\Classes\PhpExtension > PhpRunner`
 - Corrected code-smell and code style issues in `\ncc\Objects > ProjectConfiguraiton`
 - Corrected code-smell and code style issues in `\ncc\Managers > ProjectManager`
 - Corrected code-smell and code style issues in `\ncc\Classes\Extensions\PythonExtension > Python2Runner`
 - Corrected code-smell and code style issues in `\ncc\Classes\Extensions\PythonExtension > Python3Runner`
 - Corrected code-smell and code style issues in `\ncc\Classes\Extensions\PythonExtension > PythonRunner`
 - Corrected code-smell and code style issues in `\ncc\Interfaces > RunnerInterface`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects > Package`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects > Vault`
 - Corrected code-smell and code style issues in `\ncc\Objects > Vault`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects > ProjectConfiguration`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects > ExecutionPointers`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects > DefinedRemoteSource`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\Vault > Entry`
 - Corrected code-smell and code style issues in `\ncc\Objects\Vault\Password > AccessToken`
 - Corrected code-smell and code style issues in `\ncc\Objects\Vault\Password > UsernamePassword`
 - Extended `PasswordInterface` with `BytecodeObjectInterface`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\SymlinkDictionary > SymlinkEntry`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > UpdateSource`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > Project`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > Installer`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > ExecutionPolicy`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > Dependecy`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > Compiler`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > Build`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration > Assembly`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration\UpdateSource > Repository`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration\ExecutionPolicy > Execute`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration\ExecutionPolicy > ExitHandle`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration\ExecutionPolicy > ExitHandlers`
 - Implemented interface `BytecodeObjectInterface` into `\ncc\Objects\ProjectConfiguration\Build > BuildConfiguration`
 - Updated `\ncc\Classes > GitClient > cloneRepository()` to throw `GitException` instead of `GitCloneException`
 - Updated `\ncc\Classes > GitClient > checkout()` to throw `GitException` instead of `GitCheckoutException`
 - Corrected code-smell and code style issues in `\ncc\Objects > PackageLock`
 - Corrected code-smell and code style issues in `\ncc\Classes\PhpExtension > PhpRuntime`
 - Updated class `\ncc\Objects\ProjectConfiguration > Dependency` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration\Build > BuildConfiguration` to use method calls rather than direct
   property access
 - Updated class `\ncc\Objects\ProjectConfiguration\ExecutionPolicy > Execute` to use method calls rather than direct
   property access
 - Changed all uppercase occurrences of `NCC` to `ncc` in the entire project
 - Corrected code-smell and code style issues in `\ncc\Utilities > Base64`
 - Corrected code-smell and code style issues in `\ncc\Utilities > Console`
 - Updated class `\ncc\Objects\ProjectConfiguration\ExecutionPolicy > ExitHandle` to use method calls rather than direct
   property access
 - Updated class `\ncc\Objects\ProjectConfiguration\ExecutionPolicy > ExitHandlers` to use method calls rather than 
   direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration\UpdateSource > Repository` to use method calls rather than direct
   property access
 - Updated class `\ncc\Objects\ProjectConfiguration > Assembly` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration > Build` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration > Compiler` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration > ExecutionPolicy` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration > Installer` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration > Project` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ProjectConfiguration > UpdateSource` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\ComposerJson > Author` to use method calls rather than direct property access and
   implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ComposerJson > Autoloader` to use method calls rather than direct property access and
   implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ComposerJson > Funding` to use method calls rather than direct property access and
   implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ComposerJson > NamespacePointer` to use method calls rather than direct property access
   and implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ComposerJson > PackageLink` to use method calls rather than direct property access and
   implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ComposerJson > Suggestion` to use method calls rather than direct property access and
   implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ComposerJson > Support` to use method calls rather than direct property access and
   implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\ExecutionPointers > ExecutionPointer` to use method calls rather than direct property
   access and implemented `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\NccVersionInformation > Component` to use method calls rather than direct property access
   and implemented `SerializableObjectInterface`
 - Updated class `\ncc\Objects\Package > Component` to use method calls rather than direct property access and
   implemented `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\Package > ExecutionUnit` to use method calls rather than direct property access and
   implemented `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\Package > Header` to use method calls rather than direct property access and implemented
   `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\Package > Installer` to use method calls rather than direct property access and implemented
   `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\Package > MagicBytes` to use method calls rather than direct property access and implemented
   `SerializableObjectInterface`
 - Updated class `\ncc\Objects\Package > Resource` to use method calls rather than direct property access and implemented
   `BytecodeObjectInterface`
 -  Updated class `\ncc\Objects\PackageLock > DependencyEntry` to use method calls rather than direct property access
    and implemented `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\PackageLock > PackageEntry` to use method calls rather than direct property access
   and implemented `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\PackageLock > VersionEntry` to use method calls rather than direct property access
   and implemented `BytecodeObjectInterface`
 - Updated class `\ncc\Objects\SymlinkDictionary > SymlinkEntry` to use method calls rather than direct property access
 - Updated class `\ncc\Objects\Vault\Password > AccessToken` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > CliHelpSection` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > ComposerJson` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > ComposerLock` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > DefinedRemoteSource` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > HttpRequest` to use method calls rather than direct property access and implemented
   `SerializableObjectInterface`
 - Updated class `\ncc\Objects > HttpResponse` to use method calls rather than direct property access and implemented
   `SerializableObjectInterface`
 - Corrected code-smell and code style issues in `\ncc\Objects > HttpResponseCache`
 - Corrected code-smell and code style issues in `\ncc\Objects > InstallationPaths`
 - Updated class `\ncc\Objects > NccVersionInformation` to use method calls rather than direct property access and implemented
   `SerializableObjectInterface`
 - Updated class `\ncc\Objects > Package` to use method calls rather than direct property access and implemented
   `SerializableObjectInterface`
 - Updated class `\ncc\Objects > PackageLock` to use method calls rather than direct property access and implemented
   `BytecodeObjectInterface`
 - Updated class `\ncc\Objects > ProjectConfiguration` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > ProjectDetectionResults` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > RemotePackageInput` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > RepositoryQueryResults` to use method calls rather than direct property access
 - Updated class `\ncc\Objects > Vault` to use method calls rather than direct property access
 - `\ncc\Objects\ProjectConfiguration > Compiler`: Added Public Constructor to automatically determine the minimum and
   maximum supported compiler version for the selected extension
 - `\ncc\Objects\ProjectConfiguration > Compiler > fromArray()` throws an ConfigurationException if the property `extension` is null
 - `\ncc\Objects\ProjectConfiguration > Compiler > fromArray()` throws an NotSupportedException if the `extension` uses an
   unsupported compiler extension
 - `\ncc\Objects\ProjectConfiguration > Compiler > validate()` No longer accepts `$throw_exception` and throws an 
   `ConfigurationException` or `NotSupportedException` if the validation fails, otherwise it returns `True`.
 - `\ncc\Objects\ProjectConfiguration > Project > fromArray()` Throws an `ConfigurationException` if the property `compiler` 
   is missing in the project configuration
 - `\ncc\Objects > ProjectConfiguration > fromArray()` Throws an `ConfigurationException` if the property 'project' is
   missing in the root configuration
 - `\ncc\Objects\ProjectConfiguration > Project > __construct()` now requires the parameter `$compiler`
 - `\ncc\Objects\ProjectConfiguration > Dependency > __construct()` now requires the parameters `$name`, `$source_type`,
   `$source` and `$version`
 - `\ncc\Objects\ProjectConfiguration > Dependency > fromArray()` Throws an `ConfigurationException` if the property
   `name` is missing in the dependency configuration
 - Also updated a bunch of objects in a similar fashion to the ones above, (BuildConfiguration, Execute, ExitHandle, 
   ExitHandler, Repository, Assembly, Build, Dependency, ExecutionPolicy, Installer, Project, UpdateSource) I'm not
   going to list them all here, but you can find them in the commit history.
 - Implemented a template engine and refactored the CLI menu for the Project Manager and added a new `template` command
 - Refactored the entire package structure to ncc package structure 2.0 for memory efficiency and performance
 - Refactored execution unit system to use a new execution pointer system
 - Refactored `PhpRunner` to use the new execution pointer system
 - Refactored `BashRunner` to use the new execution pointer system
 - Refactored `LuaRunner` to use the new execution pointer system
 - Refactored `PerlRunner` to use the new execution pointer system
 - Refactored `PythonRunner` to use the new execution pointer system
 - Refactored `\ncc\Objects > RemoteRepository`
 - Refactored the repository system
 - Refactored Github's repository interface
 - Refactored Gitlab's repository interface
 - Refactored SourcesMenu in the CLI to use the new repository system
 - Updated dependency `nikic/php-parser` to version 4.17.1
 - Added a simple security measure in `\ncc\Objects\Value > Entry > unlock()` to delay returns randomly when the password
   is incorrect
 - Refactored the CLI menu system to use a return exit code system
 - Updated the installer to remove unused components and installation steps
 - Updated dependency `Symfony/Filesystem` to 6.3.1
 - Updated dependency `Symfony/polyfill-ctype` to 1.28.0
 - Enforced credential storage security by applying `600` permissions to the storage file so that only the owner can
   read/write to the file, this will require root to do anything with the credential file. A password will still be
   needed to decrypt entries in the file if any entries are encrypted.
 - Updated dependency `Symfony/polyfill-mbstring` to 1.28.0
 - Updated dependency `Symfony/polyfill-uuid` to 1.28.0
 - Updated dependency `Symfony/Process` to 6.3.4
 - Updated dependency `Symfony/Uid` to 6.3.0
 - Updated dependency `Symfony/Yaml` to 6.3.3
 - Refactored ZiProto


### Removed
 - Removed `FileNotFoundException` and `DirectoryNotFoundException` from `\ncc\Exceptions`
 - Removed the use of `InvalidScopeException` across the project
 - Removed references of Win32 from the project as Windows is not going supported
 - Removed unused exception `FileNotFoundException` in `\ncc\CLI > HelpMenu`
 - Removed unused class `\ncc\Objects > SymlinkDictionary`
 - Removed unused exception `GitCloneException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `GitCheckoutException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `GithubServiceException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `GitTagsException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `GitlabServiceException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `UnsupportedArchiveException` in `\ncc\Exceptions` in favor of `ArchiveException`
 - Removed unused exception `NotImplementedException` in `\ncc\Exceptions` in favor of `NotSupportedException`
 - Removed unused exception `UnsupportedCompilerExtensionException` in `\ncc\Exceptions` in favor of `NotSupportedException`
 - Removed unused exception `UnsupportedExtensionVersionException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `UnsupportedComponentTypeException` in `\ncc\Exceptions` in favor of `NotSupportedException`
 - Removed unused exception `UnsupportedPackageException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `UnsupportedRemoteTypeSource` in `\ncc\Exceptions` (not used)
 - Removed unused exception `UnsupportedProjectTypeException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `InvalidProjectConfigurationException` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `InvalidScopeException` in `\ncc\Exceptions` in favor of `InvalidArgumentException` (standard php)
 - Removed unused exception `InvalidCredentialsEntryException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `ComponentNotFoundException` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `InvalidPackageNameException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `InvalidVersionNumberException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `InvalidProjectException` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `InvalidProjectNameException` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `BuildConfigurationNotFoundException` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `InvalidBuildConfigurationException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `InvalidPropertyValue` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `InvalidVersionConfiguration` in `\ncc\Exceptions` (not used)
 - Removed unused exception `InvalidDependencyConfiguraiton` in `\ncc\Exceptions` (not used)
 - Removed unused exception `ProjectConfigurationNotFoundException` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `UndefinedExecutionPolicyExpection` in `\ncc\Exceptions` in favor of `ConfigurationException`
 - Removed unused exception `InvalidExecutionPolicyName` in `\ncc\Exceptions` (not used)
 - Removed unused exception `AccessDeniedException` in `\ncc\Exceptions` in favor of `AuthenticationException`
 - Removed unused exception `HttpException` in `\ncc\Exceptions` in favor of `NetworkException`
 - Removed unused exception `ComposerDisabledException` in `\ncc\Exceptions` in favor of `ComposerException`
 - Removed unused exception `ComposerNotAvailable` in `\ncc\Exceptions` in favor of `ComposerException`
 - Removed unused exception `InternalComposerNotAvailable` in `\ncc\Exceptions` in favor of `ComposerException`
 - Removed unused exception `PackagePreperationFailedException` in `\ncc\Exceptions` in favor of `PackageException`
 - Removed unused exception `PackageParsingException` in `\ncc\Exceptions` in favor of `PackageException`
 - Removed unused exception `MissingDependencyException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `PackageAlreadyInstalledException` in `\ncc\Exceptions` in favor of `PackageException`
 - Removed unused exception `PackageFetchException` in `\ncc\Exceptions` in favor of `PackageException`
 - Removed unused exception `PackageNotFoundException` in `\ncc\Exceptions` in favor of `PackageException`
 - Removed unused exception `ConstantReadonlyException` in `\ncc\Exceptions` in favor of `IntegrityException` & `\InvalidArgumentException`
 - Removed unused exception `InvalidConstantName` in `\ncc\Exceptions` in favor for `IntegrityException`
 - Removed unused exception `ComponentDecodeException` in `\ncc\Exceptions` in favor for `IntegrityException`
 - Removed unused exception `ResourceChecksumException` in `\ncc\Exceptions` in favor for `IntegrityException`
 - Removed unused exception `AutoloadGeneratorException` in `\ncc\Exceptions` in favor for `IOException`
 - Removed unused exception `SymlinkException` in `\ncc\Exceptions` in favor for `IOException`
 - Removed unused exception `PackageLockException` in `\ncc\Exceptions` in favor for `IOException`
 - Removed unused exception `UnitsNotFoundException` in `\ncc\Exceptions` (not used)
 - Removed unused exception `NoAvailableUnitsException` in `\ncc\Exceptions` in favor for `OperationException`
 - Removed unused exception `InstallationException` in `\ncc\Exceptions` in favor for `OperationException`
 - Removed all unused exceptions from the project, reduced the number of exceptions in total.
 - Removed unused `scope` property from `\ncc\Objects\ProjectConfiguration > Build`
 - Removed unused `\ncc\Objects > NccUpdateInformation`
 - Removed unused `\ncc\Objects > PhpConfiguration`
 - Removed parameter `$throw_exception` from `\ncc\Objects\ProjectConfiguration > Project > validate()`
 - Removed dependency `theseer\Autoload` in favor of ncc's own autoloader (screw you Arne Blankerts)
 - Removed runners `Python2` & `Python3` in favor of `Python`
 - Removed `\ncc\Classes\NccExtension > Runner` in favor of the new Execution Unit system
 - Removed `\ncc\Managers > ExecutionPointerManager` in favor of the new Execution Unit system
 - Removed checksum properties from Component & Resource since they are unused



## [1.0.3] - 2023-08-16

### Changed
 - Refactored `\ncc\Objects > PackageLock`
 - Updated `defuse\php-encryption` to version 2.4.0
 - Updated `\ncc\Classes > GitClient > cloneRepositor()` to clone submodules recursively by default
 - Updated `\ncc\Classes > GitClient > checkout()` to update submoudles.
 - Updated build system for Gitlab

### Fixed
 - Fixed issue in `\ncc\Managers > ExecutionPointerManager > executeUnit()` where if ttyMode is enabled in a non-tty
   environment, the process will throw an exception
   "/etc/ncc/ThirdParty/Symfony/Process/Process.php:1019 Error: TTY mode requires /dev/tty to be read/writable.", now
   ncc will display a warning if TTY mode cannot be enabled



## [1.0.2] - 2023-06-29

### Fixed
 - Fixed issue where error states are not properly handled in `ExecutionPointerManager` issue [#44](https://git.n64.cc/nosial/ncc/-/issues/44)
 - Updated \ncc\Managers > ExecutionPointerManager > executeUnit to set processes to run forever if a timeout is not set/null

### Changed
 - Updated `Symfony\Filesystem` to version 6.2.5
 - Updated `Symfony\polyfill-ctype` to version 1.27.0
 - Updated `Symfony\polyfill-mbstring` to version 1.27.0
 - Updated `Symfony\polyfill-uuid` to version 1.27.0
 - Updated `Symfony\Process` to version 6.2.5
 - Updated `Symfony\Uid` to version 6.2.5
 - Updated `Symfony\Yaml` to version 6.2.5
 - Updated `\ncc\Managers > PackageManager` to display more logging messages

### Added
 - Dockerfile for building a docker image of ncc



## [1.0.1] - 2023-02-07

### Added
 - Added file downloads progress
 - Added pass-through arguments to `composer` command, all arguments beginning with `--composer-` will be passed to the 
   `composer` command, for example `--composer-dev` will be passed as `--dev` to the `composer` command

### Fixed
 - Bug fix where resources are not decoded correctly when installing packages [#31](https://git.n64.cc/nosial/ncc/-/issues/42)
 - Fixed issue where dependency conflicts are thrown even when `--reinstall` is used
 - Properly implemented `composer.enable_internal_composer` so that warnings regarding improper configuration values are not thrown
 - Minor improvements to the CLI interface including fixing `--version`

### Changed
 - File downloads now cache the URL as a pointer to the file reducing the number of times the same file is downloaded



## [1.0.0] - 2022-01-29

### Added
 - Initial release of ncc