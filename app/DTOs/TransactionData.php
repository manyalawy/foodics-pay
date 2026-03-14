<?php

namespace App\DTOs;

use Carbon\CarbonImmutable;

class TransactionData
{
    /** @param array<string, string> $metadata */
    public function __construct(
        public readonly string $reference,
        public readonly string $amount,
        public readonly CarbonImmutable $date,
        public readonly array $metadata = [],
    ) {}
}
