<?php

namespace Ilbee\CSVResponse;

use Symfony\Component\HttpFoundation\Response;

class CSVResponse extends Response
{
    private string $fileName = 'CSVExport';
    private ?string $separator = null;

    public const COMMA = ',';
    public const SEMICOLON = ';';

    public function __construct(array $data, ?string $fileName = null, ?string $separator = self::SEMICOLON)
    {
        parent::__construct();

        $this->separator = $separator;
        if ($fileName) {
            $this->fileName = $fileName;
        }

        $this->setContent($this->initContent($data));
        $this->headers->set('Content-Type', 'text/csv');
        $this->headers->set('Content-Disposition', 'attachment; filename="'.$this->fileName.'.csv"');
    }

    private function initContent($data): string
    {
        $fp = fopen('php://temp', 'w');
        foreach ($this->prepareData($data) as $fields) {
            fputcsv($fp, $fields, $this->separator);
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
                if (is_object($value)) {
                    if (get_class($value) == 'DateTime') {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                }
                $line[] = $value;
            }
            $output[] = $line;

            $i++;
        }

        return $output;
    }
}
