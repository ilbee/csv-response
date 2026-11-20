<?php

namespace Ilbee\CSVResponse\Tests;

use Ilbee\CSVResponse\CSVResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ilbee\CSVResponse\CSVResponse
 */
class CSVResponseTest extends TestCase
{
    protected function getData(): array
    {
        $data = [];
        $data[] = [
            'firstName' => 'Marcel',
            'lastName' => 'TOTO',
        ];
        $data[] = [
            'firstName' => 'Maurice',
            'lastName' => 'TATA',
        ];
        return $data;
    }

    public function testResponse(): void
    {
        // Defaults to semicolon separator.
        $response = new CSVResponse($this->getData());
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
        // Use a comma to separate values.
        $response = new CSVResponse($this->getData(), 'my-file-name.csv', CSVResponse::COMMA);
        $this->assertSame(
            "firstName,lastName\nMarcel,TOTO\nMaurice,TATA\n",
            $response->getContent()
        );
        $this->assertEquals(
            'text/csv',
            $response->headers->get('content-type')
        );
        $this->assertEquals(
            'attachment; filename=my-file-name.csv',
            $response->headers->get('content-disposition')
        );
    }

    public function testDateTime(): void
    {
        $now = new \DateTime();
        $response = new CSVResponse([
            ['datetime' => $now],
        ]);
        $this->assertStringContainsString(
            $now->format('Y-m-d H:i:s'),
            $response->getContent()
        );
    }

    public function testDateTimeImmutable(): void
    {
        $now = new \DateTimeImmutable();
        $response = new CSVResponse([
            ['datetime' => $now],
        ]);
        $this->assertStringContainsString(
            $now->format('Y-m-d H:i:s'),
            $response->getContent()
        );
    }

    public function testNullValue(): void
    {
        $response = new CSVResponse([
            ['name' => 'Marcel', 'email' => null],
        ]);
        $this->assertSame(
            "name;email\nMarcel;\n",
            $response->getContent()
        );
    }

    public function testBooleanValues(): void
    {
        $response = new CSVResponse([
            ['name' => 'Marcel', 'active' => true, 'deleted' => false],
        ]);
        $this->assertSame(
            "name;active;deleted\nMarcel;true;false\n",
            $response->getContent()
        );
    }

    public function testBomDisabledByDefault(): void
    {
        $response = new CSVResponse($this->getData());
        $this->assertStringStartsNotWith("\xEF\xBB\xBF", $response->getContent());
    }

    public function testBomEnabled(): void
    {
        $response = new CSVResponse($this->getData(), null, CSVResponse::SEMICOLON, true);
        $content = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        // Content after BOM is the normal CSV
        $this->assertSame(
            "\xEF\xBB\xBF" . "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $content
        );
    }

    public function testCustomDateFormat(): void
    {
        $now = new \DateTime('2025-06-15 14:30:00');
        $response = new CSVResponse(
            [['datetime' => $now]],
            null,
            CSVResponse::SEMICOLON,
            false,
            'd/m/Y'
        );
        $this->assertSame(
            "datetime\n15/06/2025\n",
            $response->getContent()
        );
    }

    public function testDefaultDateFormatUnchanged(): void
    {
        $now = new \DateTime('2025-06-15 14:30:00');
        $response = new CSVResponse([
            ['datetime' => $now],
        ]);
        $this->assertStringContainsString(
            '2025-06-15 14:30:00',
            $response->getContent()
        );
    }

    public function testHeadersIncludedByDefault(): void
    {
        $response = new CSVResponse($this->getData());
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testHeadersDisabled(): void
    {
        $response = new CSVResponse(
            $this->getData(),
            null,
            CSVResponse::SEMICOLON,
            false,
            'Y-m-d H:i:s',
            false
        );
        $this->assertSame(
            "Marcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testIterableWithGenerator(): void
    {
        $generator = function () {
            yield ['firstName' => 'Marcel', 'lastName' => 'TOTO'];
            yield ['firstName' => 'Maurice', 'lastName' => 'TATA'];
        };

        $response = new CSVResponse($generator());
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testIterableWithArrayIterator(): void
    {
        $iterator = new \ArrayIterator([
            ['firstName' => 'Marcel', 'lastName' => 'TOTO'],
            ['firstName' => 'Maurice', 'lastName' => 'TATA'],
        ]);

        $response = new CSVResponse($iterator);
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testFormulaInjectionSanitized(): void
    {
        $data = [
            ['name' => '=CMD|"/C calc"!A0', 'email' => 'test@test.com'],
            ['name' => '+SUM(A1:A2)', 'email' => '-data'],
            ['name' => '@import', 'email' => "\tmalicious"],
        ];

        $response = new CSVResponse($data);
        $content = $response->getContent();

        $this->assertStringContainsString("'=CMD", $content);
        $this->assertStringContainsString("'+SUM", $content);
        $this->assertStringContainsString("'-data", $content);
        $this->assertStringContainsString("'@import", $content);
        $this->assertStringNotContainsString(";=CMD", $content);
        $this->assertStringNotContainsString(";+SUM", $content);
        $this->assertStringNotContainsString(";@import", $content);
    }

    public function testFormulaInjectionSanitizationDisabled(): void
    {
        $data = [
            ['name' => '=SUM(A1:A2)'],
        ];

        $response = new CSVResponse(
            $data,
            null,
            CSVResponse::SEMICOLON,
            false,
            'Y-m-d H:i:s',
            true,
            false
        );
        $content = $response->getContent();

        $this->assertStringContainsString('=SUM(A1:A2)', $content);
        $this->assertStringNotContainsString("'=SUM", $content);
    }

    public function testSafeValuesNotPrefixed(): void
    {
        $data = [
            ['name' => 'Marcel', 'amount' => '100'],
        ];

        $response = new CSVResponse($data);
        $content = $response->getContent();

        $this->assertStringContainsString('Marcel', $content);
        $this->assertStringNotContainsString("'Marcel", $content);
        $this->assertStringNotContainsString("'100", $content);
    }

    public function testNestedArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nested arrays are not supported');
        new CSVResponse([
            ['name' => 'Marcel', 'tags' => ['a', 'b']],
        ]);
    }

    public function testCallableDataSource(): void
    {
        $callable = function () {
            return [
                ['firstName' => 'Marcel', 'lastName' => 'TOTO'],
                ['firstName' => 'Maurice', 'lastName' => 'TATA'],
            ];
        };
        $response = new CSVResponse($callable);
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testCallableReturningNonIterableThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The callable must return an iterable.');
        new CSVResponse(function () {
            return 'not iterable';
        });
    }

    public function testFileNameSanitizesHeaderInjection(): void
    {
        $response = new CSVResponse(
            $this->getData(),
            "evil.csv\"\r\nX-Injected: true"
        );
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString("\n", $disposition);
        $this->assertStringNotContainsString('"evil.csv"', $disposition);
    }

    public function testFileNameSanitizesPathTraversal(): void
    {
        $response = new CSVResponse(
            $this->getData(),
            '../../etc/passwd'
        );
        $this->assertEquals(
            'attachment; filename=passwd',
            $response->headers->get('content-disposition')
        );
    }

    public function testFileNameFallbackOnEmpty(): void
    {
        $response = new CSVResponse(
            $this->getData(),
            "\r\n"
        );
        $this->assertEquals(
            'attachment; filename=CSVExport.csv',
            $response->headers->get('content-disposition')
        );
    }

    public function testObjectWithToStringIsConverted(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return 'string-value';
            }
        };

        $response = new CSVResponse([
            ['name' => 'Marcel', 'value' => $obj],
        ]);
        $this->assertStringContainsString('string-value', $response->getContent());
    }

    public function testObjectWithoutToStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be converted to string');

        new CSVResponse([
            ['name' => 'Marcel', 'value' => new \stdClass()],
        ]);
    }

    public function testFormulaInjectionInHeaders(): void
    {
        $data = [
            ['=CMD' => 'value1', '+SUM' => 'value2', '@import' => 'value3'],
        ];

        $response = new CSVResponse($data);
        $content = $response->getContent();

        $this->assertStringContainsString("'=CMD", $content);
        $this->assertStringContainsString("'+SUM", $content);
        $this->assertStringContainsString("'@import", $content);
    }

    public function testMaxRowsAllowsDataWithinLimit(): void
    {
        $response = new CSVResponse(
            $this->getData(),
            null,
            CSVResponse::SEMICOLON,
            false,
            'Y-m-d H:i:s',
            true,
            true,
            10
        );
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testMaxRowsThrowsWhenExceeded(): void
    {
        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('maximum allowed number of rows (1)');

        new CSVResponse(
            $this->getData(),
            null,
            CSVResponse::SEMICOLON,
            false,
            'Y-m-d H:i:s',
            true,
            true,
            1
        );
    }

    public function testMaxRowsNullMeansNoLimit(): void
    {
        $response = new CSVResponse($this->getData());
        $this->assertSame(
            "firstName;lastName\nMarcel;TOTO\nMaurice;TATA\n",
            $response->getContent()
        );
    }

    public function testBackslashCannotBreakOutOfEnclosure(): void
    {
        $response = new CSVResponse([
            ['a' => 'ok', 'b' => 'x\";=1+1'],
        ]);
        $this->assertSame(
            "a;b\nok;\"x\\\"\";=1+1\"\n",
            $response->getContent()
        );
    }

    public function testBackslashCannotInjectRow(): void
    {
        $response = new CSVResponse([
            ['a' => 'ok', 'b' => "x\\\"\nINJECTED;ROW"],
        ]);
        $this->assertSame(
            "a;b\nok;\"x\\\"\"\nINJECTED;ROW\"\n",
            $response->getContent()
        );
    }

    public function testStringableObjectIsSanitized(): void
    {
        $obj = new class {
            public function __toString(): string
            {
                return '=HYPERLINK("http://evil")';
            }
        };

        $response = new CSVResponse([['value' => $obj]]);
        $this->assertSame(
            "value\n\"'=HYPERLINK(\"\"http://evil\"\")\"\n",
            $response->getContent()
        );
    }

    public function testForeignSeparatorIsEnclosed(): void
    {
        $response = new CSVResponse([['a' => 'x,=1+1']]);
        $this->assertSame("a\n\"x,=1+1\"\n", $response->getContent());

        $response = new CSVResponse([['a' => 'x;=1+1']], null, CSVResponse::COMMA);
        $this->assertSame("a\n\"x;=1+1\"\n", $response->getContent());
    }

    /**
     * @dataProvider invalidSeparatorProvider
     */
    public function testInvalidSeparatorThrows(?string $separator): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The separator must be a single character');
        new CSVResponse($this->getData(), null, $separator);
    }

    public function invalidSeparatorProvider(): array
    {
        return [[null], [''], [';;'], ['"'], ["\n"]];
    }

    public function testFileNameWindowsPathTraversal(): void
    {
        $response = new CSVResponse($this->getData(), 'C:\\Users\\..\\evil.csv');
        $this->assertEquals(
            'attachment; filename=evil.csv',
            $response->headers->get('content-disposition')
        );
    }

    public function testFileNameNonAsciiGetsAsciiFallback(): void
    {
        $response = new CSVResponse($this->getData(), 'rapport-été.csv');
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('filename=rapport-_t_.csv', $disposition);
        $this->assertStringContainsString("filename*=utf-8''rapport-%C3%A9t%C3%A9.csv", $disposition);
    }

    public function testFileNameWithPercentGetsAsciiFallback(): void
    {
        $response = new CSVResponse($this->getData(), '100%.csv');
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('filename=100_.csv', $disposition);
        $this->assertStringContainsString("filename*=utf-8''100%25.csv", $disposition);
    }
}
