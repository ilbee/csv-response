<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\Response;

class CSVResponse extends Response implements CSVResponseInterface
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
        parent::__construct();

        $this->initCSVProperties($fileName, $separator, $dateFormat, $sanitizeFormulas);

        $fp = fopen('php://temp', 'w+');
        if ($addBom) {
            fwrite($fp, "\xEF\xBB\xBF");
        }
        $this->writeCSV($fp, $this->resolveData($data), $includeHeaders, $maxRows);
        rewind($fp);
        $this->setContent(stream_get_contents($fp));
        fclose($fp);

        $this->setCSVHeaders();
    }
}
