<?php

namespace App\Services\Parsers;

use App\Contracts\BankParserInterface;
use InvalidArgumentException;

class BankParserFactory
{
    /** @param array<string, class-string<BankParserInterface>> $parsers */
    public function __construct(
        private readonly array $parsers = [],
    ) {}

    public function make(string $bankName): BankParserInterface
    {
        $parsers = $this->parsers ?: config('banks.parsers', []);
        $parserClass = $parsers[$bankName] ?? null;

        if (! $parserClass) {
            throw new InvalidArgumentException("No parser registered for bank: {$bankName}");
        }

        return app($parserClass);
    }
}
