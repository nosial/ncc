# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [2.0.4] - Unreleased

This update introduces minor bug fixes.

### Fixed
 - Improve build efficiency by preventing duplicate merges
 - Updated file tracking in Runtime class
 - Fixed division by zero in ConsoleProgressBar
 - Fixed issue where progress bar is displayed in VERBOSE mode


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
   version, this is to prevent errors when the package lock contains a version that is not available in the repository.
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