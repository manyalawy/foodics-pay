<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

class ParseResult
{
    /**
     * @param  Collection<int, TransactionData>  $transactions
     * @param  list<array{line: int, input: string, error: string}>  $errors
     */
    public function __construct(
        public readonly Collection $transactions,
        public readonly array $errors = [],
    ) {}

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function totalLines(): int
    {
        return $this->transactions->count() + count($this->errors);
    }

    public function failedLines(): int
    {
        return count($this->errors);
    }
}
