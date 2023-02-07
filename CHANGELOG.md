# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - Unreleased

### Added
- Added file downloads progress

### Fixed

- Bug fix where resources are not decoded correctly when installing packages [#31](https://git.n64.cc/nosial/ncc/-/issues/42)
- Fixed issue where dependency conflicts are thrown even when `--reinstall` is used
- Properly implemented `composer.enable_internal_composer` so that warnings regarding improper configuration values are not thrown

### Changed

- File downloads now cache the URL as a pointer to the file reducing the number of times the same file is downloaded

## [1.0.0] - 2022-01-29

### Added

- Initial release of NCC