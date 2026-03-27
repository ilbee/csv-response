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
        bool $sanitizeFormulas = true
    ) {
        parent::__construct();

        $this->initCSVProperties($fileName, $separator, $dateFormat, $sanitizeFormulas);

        $content = $this->initContent($this->resolveData($data), $includeHeaders);
        if ($addBom) {
            $content = "\xEF\xBB\xBF" . $content;
        }
        $this->setContent($content);
        $this->headers->set('Content-Type', 'text/csv');
        $this->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="%s"', $this->fileName)
        );
    }

    private function initContent(iterable $data, bool $includeHeaders = true): string
    {
        $fp = fopen('php://temp', 'w');
        $i = 0;
        foreach ($data as $row) {
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

        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content;
    }
}
