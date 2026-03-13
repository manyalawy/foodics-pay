<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface BankParserInterface
{
    /** @return Collection<int, \App\DTOs\TransactionData> */
    public function parse(string $rawBody): Collection;
}
