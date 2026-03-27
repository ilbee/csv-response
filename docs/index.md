# Documentation

## Architecture

The library provides two response classes that share a common interface and trait:

```
CSVResponseInterface          (constants: COMMA, SEMICOLON, DOUBLEQUOTE, DOUBLESLASH)
    |
CSVResponseTrait              (shared logic: value conversion, sanitization, data resolution)
    |
    +-- CSVResponse            extends Symfony\Component\HttpFoundation\Response
    +-- StreamedCSVResponse    extends Symfony\Component\HttpFoundation\StreamedResponse
```

### CSVResponse

Buffers the entire CSV content in memory via `php://temp`, then sets it as the response body. Simple and convenient for small to medium datasets.

### StreamedCSVResponse

Writes rows directly to `php://output` inside a Symfony `StreamedResponse` callback. No buffering — rows are sent to the client as they are generated. Ideal for large exports or when using database cursors/generators.

### CSVResponseInterface

Carries the shared constants used by both classes:

| Constant | Value | Description |
|---|---|---|
| `COMMA` | `,` | Comma separator |
| `SEMICOLON` | `;` | Semicolon separator (default) |
| `DOUBLEQUOTE` | `"` | CSV enclosure character |
| `DOUBLESLASH` | `\` | CSV escape character |

### CSVResponseTrait

Contains all shared logic:

| Method | Description |
|---|---|
| `resolveData($data)` | If `$data` is a callable, invokes it and validates the result is iterable |
| `convertValue($key, $value)` | Converts DateTime, bool, null values; throws on nested arrays |
| `sanitizeValue($value)` | Prefixes formula-triggering characters with `'` to prevent CSV injection |
| `convertRow($row)` | Applies `convertValue` + `sanitizeValue` to each cell in a row |
| `extractHeaders($row)` | Returns array keys as header row |
| `initCSVProperties(...)` | Sets fileName, separator, dateFormat, sanitizeFormulas |

## API Reference

### Constructor

Both `CSVResponse` and `StreamedCSVResponse` share the same constructor signature:

```php
new CSVResponse(
    $data,
    ?string $fileName = null,
    ?string $separator = CSVResponseInterface::SEMICOLON,
    bool $addBom = false,
    string $dateFormat = 'Y-m-d H:i:s',
    bool $includeHeaders = true,
    bool $sanitizeFormulas = true
);
```

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$data` | `iterable\|callable` | *(required)* | Data source. Arrays, generators, iterators, or a callable returning an iterable. |
| `$fileName` | `?string` | `CSVExport.csv` | Name of the downloaded file |
| `$separator` | `?string` | `;` (semicolon) | Field separator |
| `$addBom` | `bool` | `false` | Prepend UTF-8 BOM (useful for Excel) |
| `$dateFormat` | `string` | `Y-m-d H:i:s` | Format string for `DateTimeInterface` values |
| `$includeHeaders` | `bool` | `true` | Include a header row from array keys |
| `$sanitizeFormulas` | `bool` | `true` | Prefix formula-triggering values with `'` |

## Data Sources

### Array

```php
$data = [
    ['name' => 'Marcel', 'email' => 'marcel@example.com'],
    ['name' => 'Maurice', 'email' => 'maurice@example.com'],
];

return new CSVResponse($data);
```

### Generator

```php
function generateRows(): \Generator {
    yield ['name' => 'Marcel', 'email' => 'marcel@example.com'];
    yield ['name' => 'Maurice', 'email' => 'maurice@example.com'];
}

return new StreamedCSVResponse(generateRows());
```

### Callable (lazy initialization)

The callable is invoked only when the response is sent. This is particularly useful with `StreamedCSVResponse` to defer database queries until send-time:

```php
return new StreamedCSVResponse(function () use ($repository) {
    return $repository->findAllAsGenerator();
});
```

The callable must return an `iterable`. If it returns anything else, an `InvalidArgumentException` is thrown.

### Iterator

```php
$iterator = new \ArrayIterator([
    ['name' => 'Marcel'],
    ['name' => 'Maurice'],
]);

return new CSVResponse($iterator);
```

## Value Conversion

Values are automatically converted before being written to the CSV:

| PHP Type | CSV Output | Example |
|---|---|---|
| `string` | As-is | `"hello"` |
| `int`, `float` | As-is | `42`, `3.14` |
| `DateTimeInterface` | Formatted with `$dateFormat` | `2025-06-15 14:30:00` |
| `bool` | `"true"` or `"false"` | `true` |
| `null` | Empty string | `` |
| `array` | Throws `InvalidArgumentException` | - |

## CSV Injection Protection

By default, values starting with formula-triggering characters are prefixed with a single quote (`'`) to prevent CSV injection attacks in spreadsheet applications.

Protected prefixes: `=`, `+`, `-`, `@`, `\t`, `\r`, `\n`

| Input | Output (sanitized) |
|---|---|
| `=CMD\|"/C calc"!A0` | `'=CMD\|"/C calc"!A0` |
| `+SUM(A1:A2)` | `'+SUM(A1:A2)` |
| `@import` | `'@import` |

To disable sanitization (e.g. when values are already trusted):

```php
return new CSVResponse(
    $data,
    null,
    CSVResponseInterface::SEMICOLON,
    false,
    'Y-m-d H:i:s',
    true,
    false  // disable formula sanitization
);
```

## UTF-8 BOM

When `$addBom` is `true`, a UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) is prepended to the output. This helps Microsoft Excel correctly detect UTF-8 encoding when opening the CSV file.

```php
return new CSVResponse($data, 'export.csv', CSVResponseInterface::SEMICOLON, true);
```

## Behavioral Differences

While both classes share the same API, there are subtle behavioral differences:

| Behavior | CSVResponse | StreamedCSVResponse |
|---|---|---|
| Memory usage | Entire CSV buffered in memory | Constant memory (row by row) |
| `getContent()` | Returns CSV string | Not available (streaming) |
| Data errors | Thrown at construction time | Thrown at send-time (`sendContent()`) |
| Callable resolution | At construction time | At send-time (inside callback) |
