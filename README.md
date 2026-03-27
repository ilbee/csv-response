# CSV Response

[![CI](https://github.com/ilbee/csv-response/actions/workflows/php.yml/badge.svg)](https://github.com/ilbee/csv-response/actions/workflows/php.yml)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-8892BF)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-4.4%20%7C%205.x%20%7C%206.x%20%7C%207.x-black)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-MIT-blue)](LICENSE)

A Symfony component that lets you return CSV file downloads directly from your controllers.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

## Features

- Returns a CSV download response from any Symfony controller
- Automatic header row generation from array keys (can be disabled)
- Configurable separator (semicolon by default, comma, etc.)
- Custom file name support
- DateTime objects are automatically formatted (configurable format)
- Optional UTF-8 BOM for Excel compatibility
- No configuration required — just install and use

## Installation

```bash
composer require ilbee/csv-response
```

## Usage

### Basic example

```php
use Ilbee\CSVResponse\CSVResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    #[Route('/export', name: 'export_csv')]
    public function export(): CSVResponse
    {
        $data = [
            ['firstName' => 'Marcel', 'lastName' => 'TOTO'],
            ['firstName' => 'Maurice', 'lastName' => 'TATA'],
        ];

        return new CSVResponse($data);
    }
}
```

This triggers a download of `CSVExport.csv` with the content:

```csv
firstName;lastName
Marcel;TOTO
Maurice;TATA
```

### Custom file name

```php
return new CSVResponse($data, 'users.csv');
```

### Custom separator

```php
use Ilbee\CSVResponse\CSVResponse;

// Use comma instead of semicolon
return new CSVResponse($data, 'users.csv', CSVResponse::COMMA);
```

### UTF-8 BOM (for Excel)

```php
return new CSVResponse($data, 'users.csv', CSVResponse::SEMICOLON, true);
```

### Custom date format

```php
return new CSVResponse($data, 'users.csv', CSVResponse::SEMICOLON, false, 'd/m/Y');
```

### Without header row

```php
return new CSVResponse($data, 'users.csv', CSVResponse::SEMICOLON, false, 'Y-m-d H:i:s', false);
```

## API Reference

### `CSVResponse::__construct()`

```php
new CSVResponse(
    array $data,
    ?string $fileName = null,
    ?string $separator = self::SEMICOLON,
    bool $addBom = false,
    string $dateFormat = 'Y-m-d H:i:s',
    bool $includeHeaders = true
)
```

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$data` | `array` | *(required)* | Array of associative arrays. Keys become the header row. |
| `$fileName` | `?string` | `CSVExport.csv` | Name of the downloaded file |
| `$separator` | `?string` | `;` (semicolon) | Field separator |
| `$addBom` | `bool` | `false` | Prepend UTF-8 BOM (useful for Excel) |
| `$dateFormat` | `string` | `Y-m-d H:i:s` | Format string for DateTime values |
| `$includeHeaders` | `bool` | `true` | Include a header row from array keys |

### Constants

| Constant | Value | Description |
|---|---|---|
| `CSVResponse::COMMA` | `,` | Comma separator |
| `CSVResponse::SEMICOLON` | `;` | Semicolon separator (default) |

## Contributing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpcs ./src
```

## Credits

Special thanks to [Paul Mitchum](https://github.com/paul-m) and [Dan Feder](https://github.com/dafeder) for their contributions.

## License

[MIT](LICENSE)
