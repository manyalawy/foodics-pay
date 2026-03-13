<?php

namespace App\Contracts;

use App\DTOs\TransactionData;
use Illuminate\Support\Collection;

interface BankParserInterface
{
    /** @return Collection<int, TransactionData> */
    public function parse(string $rawBody): Collection;
}
