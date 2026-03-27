# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`ilbee/csv-response` — a single-class Symfony library that returns CSV file downloads from controllers. Extends `Symfony\Component\HttpFoundation\Response`. Supports PHP >=7.4 <9, Symfony 4–7.

## Commands

```bash
composer test                # Run PHPUnit tests
composer cs-check            # Check code style (php-cs-fixer, dry-run)
composer cs-fix              # Auto-fix code style
composer phpstan             # Static analysis (level 5, src/ only)
composer test-coverage       # Tests with coverage (requires pcov)
vendor/bin/phpunit --filter testMethodName  # Run a single test
```

## Architecture

Single class: `src/CSVResponse.php` (`Ilbee\CSVResponse\CSVResponse`). It extends Symfony's `Response`, builds CSV content in-memory via `php://temp` stream using `fputcsv`, and sets `Content-Type: text/csv` with a download disposition header.

Value conversion in `prepareData()`: `DateTimeInterface` → `Y-m-d H:i:s`, bools → `"true"/"false"`, nulls → empty string, nested arrays → throws `InvalidArgumentException`.

Default separator is semicolon (`;`), not comma. Constants: `COMMA`, `SEMICOLON`, `DOUBLEQUOTE`, `DOUBLESLASH`.

## CI

GitHub Actions (`.github/workflows/ci.yml`): php-cs-fixer, PHPStan (highest+lowest deps), PHPUnit matrix across PHP 8.1–8.4 × Symfony 6.4/7.0.

## Code Style

Multi-line function signatures: when a function/method has multiple parameters, put each parameter on its own line.

## PHP Compatibility

Code must remain compatible with PHP 7.4 (no `match`, no union types, no named args). Recent commit explicitly reverted PHP 8.0+ syntax for this reason.
