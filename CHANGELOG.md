# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Nothing yet

### Changed
- Nothing yet

### Fixed
- Nothing yet

## [0.1.0] - 2026-02-09

### Added

- Initial release of Plugin Update Checker
- GitHub Releases API integration with cURL
- File-based caching with configurable TTL (default: 6 hours)
- Output buffer-based CSS/JS injection (same pattern as Priority Icons)
- Update badges in plugin list with version number and link to release
- JavaScript DOM manipulation with PJAX support
- XSS-safe URL validation (http/https only)
- Explicit SSL verification for API calls
- Crash-protected output buffer callback (try-catch)
- Optional GitHub Personal Access Token for higher rate limits
- Admin configuration for cache duration and token
- PHPUnit test suite (32 tests)

### Security

- URL protocol validation prevents javascript: and data: URI injection
- JSON encoding with `JSON_HEX_TAG` prevents script injection
- SSL certificate verification enforced on all API calls
- No database modifications

[Unreleased]: https://github.com/markus-michalski/osticket-plugin-update-check/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/markus-michalski/osticket-plugin-update-check/releases/tag/v0.1.0
