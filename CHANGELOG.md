# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.9.0] - 2026-03-27

### Added

- Optional `maxRows` parameter on both `CSVResponse` and `StreamedCSVResponse` to limit the number of rows and prevent unbounded memory usage (throws `OverflowException` when exceeded)
- Explicit `InvalidArgumentException` when a data value is an object without `__toString()`, instead of a raw PHP error

### Security

- `StreamedCSVResponse` now uses `HeaderUtils::makeDisposition()` for the `Content-Disposition` header (aligned with `CSVResponse`)

## [1.8.1] - 2026-03-27

### Security

- Use Symfony's `HeaderUtils::makeDisposition()` instead of manual `sprintf` for `Content-Disposition` header, preventing HTTP header injection via crafted filenames (RFC 6266 compliant)

## [1.8.0] - 2026-03-27

### Changed

- Dropped Symfony 4.x support (EOL since November 2023)
- Updated `symfony/http-foundation` constraint to `^5.4 || ^6.4 || ^7.0 || ^8.0`
- Extended CI matrix to test Symfony 5.4 and 8.0

## [1.7.1] - 2026-03-27

### Security

- Fix HTTP header injection via `Content-Disposition` filename (sanitize CRLF, quotes, null bytes, path traversal)
- Fix CSV formula injection in column headers (apply sanitization to headers, not just cell values)

### Added

- 4 new tests for filename sanitization and header formula injection

## [1.7.0] - 2026-03-27

### Added

- `StreamedCSVResponse` class extending `StreamedResponse` for large CSV exports with constant memory usage
- `CSVResponseInterface` with shared constants (`COMMA`, `SEMICOLON`, `DOUBLEQUOTE`, `DOUBLESLASH`)
- `CSVResponseTrait` to share CSV logic between `CSVResponse` and `StreamedCSVResponse`
- Callable data source support (callable returning an iterable)

### Changed

- Refactored `CSVResponse` to use `CSVResponseInterface` and `CSVResponseTrait`

## [1.6.1] - 2026-03-27

### Security

- CSV formula injection protection: values starting with `=`, `+`, `-`, `@`, tab, CR, LF are prefixed with a single quote

### Added

- Support for iterable data sources (generators, `ArrayIterator`, etc.)

## [1.6.0] - 2026-03-27

### Added

- Option to disable header row generation (`$includeHeaders` parameter)
- Configurable DateTime format (`$dateFormat` parameter, default `Y-m-d H:i:s`)

## [1.5.0] - 2026-03-27

### Added

- Optional UTF-8 BOM prefix for Excel compatibility (`$addBom` parameter)

## [1.4.0] - 2026-03-27

### Added

- Handle `null` values (converted to empty string), booleans (`true`/`false`), and nested arrays (throws `InvalidArgumentException`)
- Fix `DateTimeImmutable` formatting (now handled via `DateTimeInterface`)
- Modernized CI: php-cs-fixer, PHPStan, split jobs, PHP/Symfony matrix

### Fixed

- Reverted PHP 8.0+ syntax to maintain PHP 7.4 compatibility

## [1.3.0] - 2025-03-10

### Changed

- Extended CI matrix for newer PHP versions
- Made `fputcsv` escape parameter explicit

## [1.2.0] - 2024-02-05

### Changed

- Updated Symfony and PHP version constraints in `composer.json`

## [1.1.1] - 2023-03-12

### Changed

- Broadened `symfony/http-foundation` version constraint

## [1.1.0] - 2023-03-11

### Added

- PHPUnit test suite with PHP version matrix
- GitHub Actions CI workflow

## [1.0.4] - 2021-03-26

### Changed

- README updates

[1.9.0]: https://github.com/ilbee/csv-response/compare/1.8.1...1.9.0
[1.8.1]: https://github.com/ilbee/csv-response/compare/1.8.0...1.8.1
[1.8.0]: https://github.com/ilbee/csv-response/compare/1.7.1...1.8.0
[1.7.1]: https://github.com/ilbee/csv-response/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/ilbee/csv-response/compare/1.6.1...1.7.0
[1.6.1]: https://github.com/ilbee/csv-response/compare/1.6.0...1.6.1
[1.6.0]: https://github.com/ilbee/csv-response/compare/1.5.0...1.6.0
[1.5.0]: https://github.com/ilbee/csv-response/compare/1.4.0...1.5.0
[1.4.0]: https://github.com/ilbee/csv-response/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/ilbee/csv-response/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/ilbee/csv-response/compare/1.1.1...1.2.0
[1.1.1]: https://github.com/ilbee/csv-response/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/ilbee/csv-response/compare/1.0.4...1.1.0
[1.0.4]: https://github.com/ilbee/csv-response/releases/tag/1.0.4
