<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\Response;

class CSVResponse extends Response
{
    private string $fileName = 'CSVExport.csv';
    private ?string $separator = null;

    public const COMMA = ',';
    public const SEMICOLON = ';';
    public const DOUBLEQUOTE = '"';
    public const DOUBLESLASH = '\\';

    public function __construct(array $data, ?string $fileName = null, ?string $separator = self::SEMICOLON)
    {
        parent::__construct();

        $this->separator = $separator;
        if ($fileName) {
            $this->setFileName($fileName);
        }

        $this->setContent($this->initContent($data));
        $this->headers->set('Content-Type', 'text/csv');
        $this->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $this->fileName));
    }

    private function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    private function initContent(array $data): string
    {
        $fp = fopen('php://temp', 'w');
        foreach ($this->prepareData($data) as $fields) {
            fputcsv($fp, $fields, $this->separator, self::DOUBLEQUOTE, self::DOUBLESLASH);
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content;
    }

    private function prepareData(array $data): array
    {
        $i = 0;
        $output = [];
        foreach ($data as $row) {
            if ($i === 0) {
                $head = [];
                foreach ($row as $key => $value) {
                    $head[] = $key;
                }
                $output[] = $head;
            }

            $line = [];
            foreach ($row as $key => $value) {
                if (is_object($value) && get_class($value) == 'DateTime') {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $line[] = $value;
            }
            $output[] = $line;

            $i++;
        }

        return $output;
    }
}
