<?php

namespace Tests\Unit;

use App\Services\Parsers\AcmeBankParser;
use PHPUnit\Framework\TestCase;

class AcmeBankParserTest extends TestCase
{
    private AcmeBankParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AcmeBankParser;
    }

    public function test_parses_single_line(): void
    {
        $body = '156,50//202506159000001//20250615';

        $result = $this->parser->parse($body);

        $this->assertCount(1, $result->transactions);
        $tx = $result->transactions->first();
        $this->assertEquals('202506159000001', $tx->reference);
        $this->assertEquals('156.50', $tx->amount);
        $this->assertEquals('2025-06-15', $tx->date->toDateString());
        $this->assertEmpty($tx->metadata);
    }

    public function test_parses_multiple_lines(): void
    {
        $body = "156,50//REF001//20250615\n200,00//REF002//20250616";

        $result = $this->parser->parse($body);

        $this->assertCount(2, $result->transactions);
        $this->assertEquals('REF001', $result->transactions->first()->reference);
        $this->assertEquals('REF002', $result->transactions->last()->reference);
    }

    public function test_normalizes_amount_comma_to_dot(): void
    {
        $body = '1234,56//REF001//20250615';

        $result = $this->parser->parse($body);

        $this->assertEquals('1234.56', $result->transactions->first()->amount);
    }

    public function test_parses_date_correctly(): void
    {
        $body = '100,00//REF001//20251231';

        $result = $this->parser->parse($body);

        $this->assertEquals('2025-12-31', $result->transactions->first()->date->toDateString());
    }

    public function test_filters_empty_lines(): void
    {
        $body = "156,50//REF001//20250615\n\n200,00//REF002//20250616\n";

        $result = $this->parser->parse($body);

        $this->assertCount(2, $result->transactions);
    }

    public function test_skips_malformed_lines(): void
    {
        $body = "156,50//REF001//20250615\nBADLINE\n200,00//REF002//20250616";

        $result = $this->parser->parse($body);

        $this->assertCount(2, $result->transactions);
        $this->assertTrue($result->hasErrors());
        $this->assertCount(1, $result->errors);
        $this->assertEquals(2, $result->errors[0]['line']);
    }

    public function test_skips_lines_with_negative_amounts(): void
    {
        $body = '-100,00//REF001//20250615';

        $result = $this->parser->parse($body);

        $this->assertCount(0, $result->transactions);
        $this->assertTrue($result->hasErrors());
    }

    public function test_skips_lines_with_invalid_dates(): void
    {
        $body = '100,00//REF001//20251332';

        $result = $this->parser->parse($body);

        $this->assertCount(0, $result->transactions);
        $this->assertTrue($result->hasErrors());
    }
}
