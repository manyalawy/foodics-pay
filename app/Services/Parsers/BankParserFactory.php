<?php

namespace App\Services\Parsers;

use App\Contracts\BankParserInterface;
use InvalidArgumentException;

class BankParserFactory
{
    /** @var array<string, class-string<BankParserInterface>> */
    private array $parsers = [
        'foodics' => FoodicsBankParser::class,
        'acme' => AcmeBankParser::class,
    ];

    public function make(string $bankName): BankParserInterface
    {
        $parserClass = $this->parsers[$bankName] ?? null;

        if (! $parserClass) {
            throw new InvalidArgumentException("No parser registered for bank: {$bankName}");
        }

        return app($parserClass);
    }
}
