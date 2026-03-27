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
            'attachment; filename="my-file-name.csv"',
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

    public function testNestedArrayThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Nested arrays are not supported');
        new CSVResponse([
            ['name' => 'Marcel', 'tags' => ['a', 'b']],
        ]);
    }
}
