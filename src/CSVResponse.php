<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\Response;

class CSVResponse extends Response
{
    private string $fileName = 'CSVExport.csv';
    private ?string $separator = null;
    private string $dateFormat = 'Y-m-d H:i:s';

    public const COMMA = ',';
    public const SEMICOLON = ';';
    public const DOUBLEQUOTE = '"';
    public const DOUBLESLASH = '\\';

    public function __construct(
        iterable $data,
        ?string $fileName = null,
        ?string $separator = self::SEMICOLON,
        bool $addBom = false,
        string $dateFormat = 'Y-m-d H:i:s',
        bool $includeHeaders = true
    ) {
        parent::__construct();

        $this->separator = $separator;
        $this->dateFormat = $dateFormat;
        if ($fileName) {
            $this->setFileName($fileName);
        }

        $content = $this->initContent($data, $includeHeaders);
        if ($addBom) {
            $content = "\xEF\xBB\xBF" . $content;
        }
        $this->setContent($content);
        $this->headers->set('Content-Type', 'text/csv');
        $this->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $this->fileName));
    }

    private function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    private function initContent(iterable $data, bool $includeHeaders = true): string
    {
        $fp = fopen('php://temp', 'w');
        foreach ($this->prepareData($data, $includeHeaders) as $fields) {
            fputcsv($fp, $fields, $this->separator, self::DOUBLEQUOTE, self::DOUBLESLASH);
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content;
    }

    private function prepareData(iterable $data, bool $includeHeaders = true): array
    {
        $i = 0;
        $output = [];
        foreach ($data as $row) {
            if ($i === 0 && $includeHeaders) {
                $head = [];
                foreach ($row as $key => $value) {
                    $head[] = $key;
                }
                $output[] = $head;
            }

            $line = [];
            foreach ($row as $key => $value) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format($this->dateFormat);
                } elseif (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = '';
                } elseif (is_array($value)) {
                    throw new \InvalidArgumentException(
                        sprintf('Nested arrays are not supported in CSV data (column "%s").', $key)
                    );
                }
                $line[] = $value;
            }
            $output[] = $line;

            $i++;
        }

        return $output;
    }
}
