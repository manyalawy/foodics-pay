<?php

namespace Tests\Unit\DTOs;

use App\DTOs\TransactionData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class TransactionDataTest extends TestCase
{
    public function test_constructs_with_required_fields(): void
    {
        $date = CarbonImmutable::parse('2025-06-15');
        $dto = new TransactionData(
            reference: 'REF001',
            amount: '156.50',
            date: $date,
        );

        $this->assertEquals('REF001', $dto->reference);
        $this->assertEquals('156.50', $dto->amount);
        $this->assertEquals('2025-06-15', $dto->date->toDateString());
        $this->assertEmpty($dto->metadata);
    }

    public function test_constructs_with_metadata(): void
    {
        $dto = new TransactionData(
            reference: 'REF001',
            amount: '156.50',
            date: CarbonImmutable::parse('2025-06-15'),
            metadata: ['note' => 'test'],
        );

        $this->assertEquals(['note' => 'test'], $dto->metadata);
    }
}
