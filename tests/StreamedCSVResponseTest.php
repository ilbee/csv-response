<?php

namespace Ilbee\CSVResponse\Tests;

use Ilbee\CSVResponse\CSVResponseInterface;
use Ilbee\CSVResponse\StreamedCSVResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ilbee\CSVResponse\StreamedCSVResponse
 */
class StreamedCSVResponseTest extends TestCase
{
    protected function getData(): array
    {
        return [
            ['firstName' => 'Marcel', 'lastName' => 'TOTO'],
            ['firstName' => 'Maurice', 'lastName' => 'TATA'],
        ];
    }

    private function getStreamedContent(StreamedCSVResponse $response): string
    {
        ob_start();
        $response->sendContent();
        return ob_get_clean();
    }

    public function testResponse(): void
    {
        $response = new StreamedCSVResponse($this->getData());
        $content = $this->getStreamedContent($response);
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $content
        );
    }

    public function testCommaSeparator(): void
    {
        $response = new StreamedCSVResponse(
            $this->getData(),
            'export.csv',
            CSVResponseInterface::COMMA
        );
        $content = $this->getStreamedContent($response);
        $this->assertSame(
            "firstName,lastName\nMarcel,TOTO\nMaurice,TATA\n",
            $content
        );
    }

    public function testHeaders(): void
    {
        $response = new StreamedCSVResponse($this->getData(), 'my-file.csv');
        $this->assertEquals('text/csv', $response->headers->get('content-type'));
        $this->assertEquals(
            'attachment; filename=my-file.csv',
            $response->headers->get('content-disposition')
        );
    }

    public function testDefaultFileName(): void
    {
        $response = new StreamedCSVResponse($this->getData());
        $this->assertEquals(
            'attachment; filename=CSVExport.csv',
            $response->headers->get('content-disposition')
        );
    }

    public function testBomEnabled(): void
    {
        $response = new StreamedCSVResponse(
            $this->getData(),
            null,
            CSVResponseInterface::SEMICOLON,
            true
        );
        $content = $this->getStreamedContent($response);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertSame(
            "\xEF\xBB\xBF" . "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $content
        );
    }

    public function testBomDisabledByDefault(): void
    {
        $response = new StreamedCSVResponse($this->getData());
        $content = $this->getStreamedContent($response);
        $this->assertStringStartsNotWith("\xEF\xBB\xBF", $content);
    }

    public function testDateTime(): void
    {
        $now = new \DateTime();
        $response = new StreamedCSVResponse([['datetime' => $now]]);
        $content = $this->getStreamedContent($response);
        $this->assertStringContainsString($now->format('Y-m-d H:i:s'), $content);
    }

    public function testCustomDateFormat(): void
    {
        $now = new \DateTime('2025-06-15 14:30:00');
        $response = new StreamedCSVResponse(
            [['datetime' => $now]],
            null,
            CSVResponseInterface::SEMICOLON,
            false,
            'd/m/Y'
        );
        $content = $this->getStreamedContent($response);
        $this->assertSame("datetime\n15/06/2025\n", $content);
    }

    public function testNullValue(): void
    {
        $response = new StreamedCSVResponse([
            ['name' => 'Marcel', 'email' => null],
        ]);
        $content = $this->getStreamedContent($response);
        $this->assertSame("name;email\nMarcel;\n", $content);
    }

    public function testBooleanValues(): void
    {
        $response = new StreamedCSVResponse([
            ['name' => 'Marcel', 'active' => true, 'deleted' => false],
        ]);
        $content = $this->getStreamedContent($response);
        $this->assertSame(
            "name;active;deleted\nMarcel;true;false\n",
            $content
        );
    }

    public function testHeadersDisabled(): void
    {
        $response = new StreamedCSVResponse(
            $this->getData(),
            null,
            CSVResponseInterface::SEMICOLON,
            false,
            'Y-m-d H:i:s',
            false
        );
        $content = $this->getStreamedContent($response);
        $this->assertSame("Marcel;TOTO\nMaurice;TATA\n", $content);
    }

    public function testFormulaInjectionSanitized(): void
    {
        $data = [
            ['name' => '=CMD|"/C calc"!A0', 'email' => 'test@test.com'],
            ['name' => '+SUM(A1:A2)', 'email' => '-data'],
            ['name' => '@import', 'email' => "\tmalicious"],
        ];
        $response = new StreamedCSVResponse($data);
        $content = $this->getStreamedContent($response);

        $this->assertStringContainsString("'=CMD", $content);
        $this->assertStringContainsString("'+SUM", $content);
        $this->assertStringContainsString("'-data", $content);
        $this->assertStringContainsString("'@import", $content);
    }

    public function testFormulaInjectionSanitizationDisabled(): void
    {
        $data = [['name' => '=SUM(A1:A2)']];
        $response = new StreamedCSVResponse(
            $data,
            null,
            CSVResponseInterface::SEMICOLON,
            false,
            'Y-m-d H:i:s',
            true,
            false
        );
        $content = $this->getStreamedContent($response);
        $this->assertStringContainsString('=SUM(A1:A2)', $content);
        $this->assertStringNotContainsString("'=SUM", $content);
    }

    public function testNestedArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nested arrays are not supported');
        $response = new StreamedCSVResponse([
            ['name' => 'Marcel', 'tags' => ['a', 'b']],
        ]);
        ob_start();
        try {
            $response->sendContent();
        } finally {
            ob_end_clean();
        }
    }

    public function testCallableDataSource(): void
    {
        $callable = function () {
            return [
                ['firstName' => 'Marcel', 'lastName' => 'TOTO'],
                ['firstName' => 'Maurice', 'lastName' => 'TATA'],
            ];
        };
        $response = new StreamedCSVResponse($callable);
        $content = $this->getStreamedContent($response);
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $content
        );
    }

    public function testCallableReturningGenerator(): void
    {
        $callable = function () {
            yield ['firstName' => 'Marcel', 'lastName' => 'TOTO'];
            yield ['firstName' => 'Maurice', 'lastName' => 'TATA'];
        };
        $response = new StreamedCSVResponse($callable);
        $content = $this->getStreamedContent($response);
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $content
        );
    }

    public function testCallableReturningNonIterableThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The callable must return an iterable.');
        $response = new StreamedCSVResponse(function () {
            return 'not iterable';
        });
        ob_start();
        try {
            $response->sendContent();
        } finally {
            ob_end_clean();
        }
    }

    public function testGeneratorDataSource(): void
    {
        $generator = function () {
            yield ['firstName' => 'Marcel', 'lastName' => 'TOTO'];
            yield ['firstName' => 'Maurice', 'lastName' => 'TATA'];
        };
        $response = new StreamedCSVResponse($generator());
        $content = $this->getStreamedContent($response);
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $content
        );
    }
}
