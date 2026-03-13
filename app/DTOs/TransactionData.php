<?php

namespace App\DTOs;

use Carbon\CarbonImmutable;

class TransactionData
{
    public function __construct(
        public readonly string $reference,
        public readonly string $amount,
        public readonly CarbonImmutable $date,
        public readonly array $metadata = [],
    ) {}
}
