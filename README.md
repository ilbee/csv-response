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
- [Which class should I use?](#which-class-should-i-use)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)
- [Full Documentation](docs/index.md)

## Features

- **Two response classes** for different use cases:

| Class | Extends | Best for |
|---|---|---|
| `CSVResponse` | `Response` | Small to medium datasets (buffered in memory) |
| `StreamedCSVResponse` | `StreamedResponse` | Large datasets (streamed row by row, constant memory) |

- Automatic header row generation from array keys (can be disabled)
- Configurable separator (semicolon by default, comma, etc.)
- Custom file name support
- DateTime objects are automatically formatted (configurable format)
- Optional UTF-8 BOM for Excel compatibility
- CSV injection protection (formula sanitization)
- Accepts arrays, iterables, generators, or callables as data source
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

### Streaming large exports

```php
use Ilbee\CSVResponse\StreamedCSVResponse;

class ExportController extends AbstractController
{
    #[Route('/export/large', name: 'export_large_csv')]
    public function exportLarge(UserRepository $repository): StreamedCSVResponse
    {
        // Callable is invoked at send-time — no data buffered in memory
        return new StreamedCSVResponse(function () use ($repository) {
            foreach ($repository->findAllIterator() as $user) {
                yield [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ];
            }
        });
    }
}
```

### Custom file name and separator

```php
use Ilbee\CSVResponse\CSVResponseInterface;

return new CSVResponse($data, 'users.csv', CSVResponseInterface::COMMA);
```

### UTF-8 BOM (for Excel)

```php
return new CSVResponse($data, 'users.csv', CSVResponseInterface::SEMICOLON, true);
```

### Custom date format

```php
return new CSVResponse($data, 'users.csv', CSVResponseInterface::SEMICOLON, false, 'd/m/Y');
```

### Without header row

```php
return new CSVResponse(
    $data,
    'users.csv',
    CSVResponseInterface::SEMICOLON,
    false,
    'Y-m-d H:i:s',
    false
);
```

## Which class should I use?

| Scenario | Class |
|---|---|
| Small datasets (< 1000 rows) | `CSVResponse` |
| Need to access content after creation (`getContent()`) | `CSVResponse` |
| Large datasets or unknown size | `StreamedCSVResponse` |
| Database cursor / generator source | `StreamedCSVResponse` |
| Memory-constrained environment | `StreamedCSVResponse` |

Both classes share the same constructor signature and support the same features. The only difference is how data is written to the response.

## Contributing

```bash
composer install
composer test          # PHPUnit
composer phpstan       # Static analysis
composer cs-check      # Code style check
composer cs-fix        # Auto-fix code style
```

## Credits

Special thanks to [Paul Mitchum](https://github.com/paul-m) and [Dan Feder](https://github.com/dafeder) for their contributions.

## License

[MIT](LICENSE)
