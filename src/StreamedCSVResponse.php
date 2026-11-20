<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamedCSVResponse extends StreamedResponse implements CSVResponseInterface
{
    use CSVResponseTrait;

    /**
     * @param iterable|callable $data
     */
    public function __construct(
        $data,
        ?string $fileName = null,
        ?string $separator = self::SEMICOLON,
        bool $addBom = false,
        string $dateFormat = 'Y-m-d H:i:s',
        bool $includeHeaders = true,
        bool $sanitizeFormulas = true,
        ?int $maxRows = null
    ) {
        $this->initCSVProperties($fileName, $separator, $dateFormat, $sanitizeFormulas);

        $callback = function () use ($data, $addBom, $includeHeaders, $maxRows) {
            $fp = fopen('php://output', 'w');
            if ($addBom) {
                fwrite($fp, "\xEF\xBB\xBF");
            }
            $this->writeCSV($fp, $this->resolveData($data), $includeHeaders, $maxRows);
            fclose($fp);
        };

        parent::__construct($callback);

        $this->setCSVHeaders();
    }
}
