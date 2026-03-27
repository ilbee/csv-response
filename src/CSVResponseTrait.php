<?php

namespace Ilbee\CSVResponse;

trait CSVResponseTrait
{
    /** @var string */
    private $fileName = 'CSVExport.csv';

    /** @var string|null */
    private $separator = null;

    /** @var string */
    private $dateFormat = 'Y-m-d H:i:s';

    /** @var bool */
    private $sanitizeFormulas = true;

    /** @var array<string> */
    private static $formulaPrefixes = ['=', '+', '-', '@', "\t", "\r", "\n"];

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
        $this->separator = $separator;
        $this->dateFormat = $dateFormat;
        $this->sanitizeFormulas = $sanitizeFormulas;
        if ($fileName) {
            $this->fileName = $fileName;
        }
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
        return array_keys($row);
    }
}
