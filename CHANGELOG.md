# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.4] - Unreleased

This update introduces major changes in the codebase, including the removal of Win32 support, and the addition of new
features.

### Added
 - `LICENSE.md` & `license.md` are now detected as license files in  `\ncc\Classes\ComposerExtension > ComposerSourceBuiltin > convertProject()`
 - Added new exception `PathNotFoundException` and implemented it in replacement for `DirectoryNotFoundException` and
   `FileNotFoundException` in `\ncc\Exceptions`
 - Added a new interface class `BytecodeObjectInterface` which will be used to implement object types for compiled assets
 - Added new exception `GitException` in `\ncc\Exceptions` to replace all git related exceptions

### Fixed
 - Fixed MITM attack vector in `\ncc\Classes > HttpClient > prepareCurl()`

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

### Removed
 - Removed `FileNotFoundException` and `DirectoryNotFoundException` from `\ncc\Exceptions`
 - Removed the use of `InvalidScopeException` across the project
 - Removed references of Win32 from the project as Windows is not going supported
 - Removed unused exception `FileNotFoundException` in `\ncc\CLI > HelpMenu`
 - Removed unused class `\ncc\Objects > SymlinkDictionary`
 - Removed unused exception `GitCloneException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `GitCheckoutException` in `\ncc\Exceptions` in favor of `GitException`
 - Removed unused exception `GithubServiceException` in `\ncc\Exceptions` in favor of `GitException`



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
 - Dockerfile for building a docker image of NCC



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
 - Initial release of NCC