<?php

declare(strict_types=1);

namespace AstralShipping\Tests;

use AstralShipping\Rate\CsvRateParser;
use PHPUnit\Framework\TestCase;

final class CsvRateParserTest extends TestCase
{
    private CsvRateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvRateParser();
    }

    public function testParsesExampleCsv(): void
    {
        $path = dirname(__DIR__) . '/stubs/database/rates.example.csv';
        $rows = $this->parser->parseFile($path);

        $this->assertNotEmpty($rows);
        $this->assertSame('colissimo_home', $rows[0]['method_code']);
        $this->assertSame('FR', $rows[0]['country_code']);
        $this->assertSame(0, $rows[0]['min_weight_g']);
        $this->assertSame(500, $rows[0]['max_weight_g']);
        $this->assertSame(520, $rows[0]['price_cents']);
        $this->assertSame(2, $rows[0]['estimated_days']);
        $this->assertSame(1, $rows[0]['active']);
    }

    public function testAcceptsCommaDecimalAndSkipsComments(): void
    {
        $csv = <<<'CSV'
method_code,country,min_weight_g,max_weight_g,price_eur,estimated_days
# commentaire
colissimo_home,FR,0,500,"5,20",2
colissimo_home,BE,0,1000,9.90,

CSV;
        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, $csv);
        rewind($handle);

        $rows = $this->parser->parseHandle($handle);
        fclose($handle);

        $this->assertCount(2, $rows);
        $this->assertSame(520, $rows[0]['price_cents']);
        $this->assertSame('BE', $rows[1]['country_code']);
        $this->assertNull($rows[1]['estimated_days']);
    }

    public function testEmptyCountryBecomesWildcard(): void
    {
        $csv = <<<'CSV'
method_code,country,min_weight_g,max_weight_g,price_eur,estimated_days
colissimo_home,,0,1000,15.00,5

CSV;
        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, $csv);
        rewind($handle);

        $rows = $this->parser->parseHandle($handle);
        fclose($handle);

        $this->assertSame('*', $rows[0]['country_code']);
    }

    public function testRejectsBadHeader(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('En-tête CSV invalide');

        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, "foo,bar\n1,2\n");
        rewind($handle);
        $this->parser->parseHandle($handle);
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->parser->parseFile(__DIR__ . '/no-such-file.csv');
    }
}
