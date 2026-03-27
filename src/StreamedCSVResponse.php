<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\HeaderUtils;
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

            $i = 0;
            foreach ($this->resolveData($data) as $row) {
                if ($maxRows !== null && $i >= $maxRows) {
                    throw new \OverflowException(
                        sprintf('Data exceeds the maximum allowed number of rows (%d).', $maxRows)
                    );
                }
                if ($i === 0 && $includeHeaders) {
                    fputcsv(
                        $fp,
                        $this->extractHeaders($row),
                        $this->separator,
                        self::DOUBLEQUOTE,
                        self::DOUBLESLASH
                    );
                }
                fputcsv(
                    $fp,
                    $this->convertRow($row),
                    $this->separator,
                    self::DOUBLEQUOTE,
                    self::DOUBLESLASH
                );
                $i++;
            }

            fclose($fp);
        };

        parent::__construct($callback);

        $this->headers->set('Content-Type', 'text/csv');
        $this->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $this->fileName
            )
        );
    }
}
