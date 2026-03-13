<?php

namespace Tests\Unit;

use App\Services\Parsers\FoodicsBankParser;
use PHPUnit\Framework\TestCase;

class FoodicsBankParserTest extends TestCase
{
    private FoodicsBankParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FoodicsBankParser;
    }

    public function test_parses_single_line_with_metadata(): void
    {
        $body = '20250615156,50#202506159000001#note/debt payment march/internal_reference/A462JE81';

        $result = $this->parser->parse($body);

        $this->assertCount(1, $result);
        $tx = $result->first();
        $this->assertEquals('202506159000001', $tx->reference);
        $this->assertEquals('156.50', $tx->amount);
        $this->assertEquals('2025-06-15', $tx->date->toDateString());
        $this->assertEquals([
            'note' => 'debt payment march',
            'internal_reference' => 'A462JE81',
        ], $tx->metadata);
    }

    public function test_parses_multiple_lines(): void
    {
        $body = "20250615156,50#202506159000001#note/debt payment march\n20250616200,00#202506169000002#note/rent";

        $result = $this->parser->parse($body);

        $this->assertCount(2, $result);
        $this->assertEquals('202506159000001', $result->first()->reference);
        $this->assertEquals('202506169000002', $result->last()->reference);
    }

    public function test_handles_empty_metadata(): void
    {
        $body = '20250615156,50#202506159000001#';

        $result = $this->parser->parse($body);

        $this->assertCount(1, $result);
        $this->assertEmpty($result->first()->metadata);
    }

    public function test_normalizes_amount_comma_to_dot(): void
    {
        $body = '202506151234,56#REF001#';

        $result = $this->parser->parse($body);

        $this->assertEquals('1234.56', $result->first()->amount);
    }

    public function test_parses_date_correctly(): void
    {
        $body = '20251231100,00#REF001#';

        $result = $this->parser->parse($body);

        $this->assertEquals('2025-12-31', $result->first()->date->toDateString());
    }

    public function test_filters_empty_lines(): void
    {
        $body = "20250615156,50#REF001#\n\n20250616200,00#REF002#\n";

        $result = $this->parser->parse($body);

        $this->assertCount(2, $result);
    }
}
