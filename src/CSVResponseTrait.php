<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\HeaderUtils;

trait CSVResponseTrait
{
    /** @var string */
    private $fileName = 'CSVExport.csv';

    /** @var string */
    private $separator = self::SEMICOLON;

    /** @var string */
    private $dateFormat = 'Y-m-d H:i:s';

    /** @var bool */
    private $sanitizeFormulas = true;

    /** @var array<string> */
    private static $formulaPrefixes = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * Characters that force a field to be enclosed, in addition to the configured separator.
     * Common separators are always enclosed so that a spreadsheet splitting on a different
     * separator (locale-dependent) cannot turn part of a value into a new, unsanitized cell.
     *
     * @var array<string>
     */
    private static $enclosureTriggers = [self::DOUBLEQUOTE, "\r", "\n", "\t", ' ', self::COMMA, self::SEMICOLON];

    /**
     * @param iterable|callable $data
     * @return iterable
     */
    private function resolveData($data): iterable
    {
        if (is_callable($data)) {
            $data = $data();
            if (!is_iterable($data)) {
                throw new \InvalidArgumentException(
                    'The callable must return an iterable.'
                );
            }
        }

        return $data;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    private function convertValue(string $key, $value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->dateFormat);
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return '';
        } elseif (is_array($value)) {
            throw new \InvalidArgumentException(
                sprintf('Nested arrays are not supported in CSV data (column "%s").', $key)
            );
        } elseif (is_object($value)) {
            if (!method_exists($value, '__toString')) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Object of class "%s" cannot be converted to string (column "%s").',
                        get_class($value),
                        $key
                    )
                );
            }

            // Cast now so the result goes through formula sanitization like any other string.
            return (string) $value;
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeValue($value)
    {
        if ($this->sanitizeFormulas && is_string($value) && isset($value[0]) && in_array($value[0], self::$formulaPrefixes, true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function initCSVProperties(
        ?string $fileName,
        ?string $separator,
        string $dateFormat,
        bool $sanitizeFormulas
    ): void {
        if ($separator === null || strlen($separator) !== 1 || in_array($separator, [self::DOUBLEQUOTE, "\r", "\n"], true)) {
            throw new \InvalidArgumentException(
                'The separator must be a single character other than a double quote or a line break.'
            );
        }

        $this->separator = $separator;
        $this->dateFormat = $dateFormat;
        $this->sanitizeFormulas = $sanitizeFormulas;
        if ($fileName) {
            $this->fileName = $this->sanitizeFileName($fileName);
        }
    }

    private function sanitizeFileName(string $fileName): string
    {
        // Control characters (CR/LF, NUL, tab...) and quotes.
        $fileName = (string) preg_replace('/[\x00-\x1F\x7F"]/', '', $fileName);

        // Treat Windows-style separators as path separators too, then keep the last segment.
        $fileName = basename(str_replace('\\', '/', $fileName));

        if ($fileName === '' || $fileName === '.' || $fileName === '..') {
            return 'CSVExport.csv';
        }

        return $fileName;
    }

    private function setCSVHeaders(): void
    {
        // The fallback must be printable ASCII without "%", otherwise makeDisposition() throws.
        // One "_" per character when the name is valid UTF-8, per byte otherwise.
        $fallback = preg_replace('/[^\x20-\x7E]|%/u', '_', $this->fileName)
            ?? (string) preg_replace('/[^\x20-\x7E]|%/', '_', $this->fileName);

        $this->headers->set('Content-Type', 'text/csv');
        $this->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $this->fileName,
                $fallback
            )
        );
    }

    /**
     * @param resource $fp
     */
    private function writeCSV($fp, iterable $data, bool $includeHeaders, ?int $maxRows): void
    {
        $i = 0;
        foreach ($data as $row) {
            if ($maxRows !== null && $i >= $maxRows) {
                throw new \OverflowException(
                    sprintf('Data exceeds the maximum allowed number of rows (%d).', $maxRows)
                );
            }
            if ($i === 0 && $includeHeaders) {
                $this->writeLine($fp, $this->extractHeaders($row));
            }
            $this->writeLine($fp, $this->convertRow($row));
            $i++;
        }
    }

    /**
     * Writes one RFC 4180 line. fputcsv() is not used because it treats "\" as an escape
     * character (letting a value break out of its enclosure) and cannot force enclosure.
     *
     * @param resource $fp
     * @param array<mixed> $fields
     */
    private function writeLine($fp, array $fields): void
    {
        $triggers = self::$enclosureTriggers;
        $triggers[] = $this->separator;

        $line = [];
        foreach ($fields as $field) {
            $field = (string) $field;
            if (strpbrk($field, implode('', $triggers)) !== false) {
                $field = self::DOUBLEQUOTE . str_replace(self::DOUBLEQUOTE, self::DOUBLEQUOTE . self::DOUBLEQUOTE, $field) . self::DOUBLEQUOTE;
            }
            $line[] = $field;
        }

        fwrite($fp, implode($this->separator, $line) . "\n");
    }

    /**
     * @param array<string, mixed> $row
     * @return array<mixed>
     */
    private function convertRow(array $row): array
    {
        $line = [];
        foreach ($row as $key => $value) {
            $value = $this->convertValue($key, $value);
            $line[] = $this->sanitizeValue($value);
        }

        return $line;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string>
     */
    private function extractHeaders(array $row): array
    {
        $headers = array_keys($row);

        if ($this->sanitizeFormulas) {
            $headers = array_map(function ($header) {
                return $this->sanitizeValue($header);
            }, $headers);
        }

        return $headers;
    }
}
