<?php

namespace App\Services\Parsers;

use App\Contracts\BankParserInterface;
use App\DTOs\TransactionData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AcmeBankParser implements BankParserInterface
{
    public function parse(string $rawBody): Collection
    {
        $lines = array_filter(explode("\n", $rawBody), fn (string $line) => trim($line) !== '');

        return collect($lines)->map(function (string $line) {
            $parts = explode('//', $line);

            $amount = str_replace(',', '.', $parts[0]);
            $reference = $parts[1];
            $date = CarbonImmutable::createFromFormat('Ymd', $parts[2]);

            return new TransactionData(
                reference: $reference,
                amount: $amount,
                date: $date,
            );
        });
    }
}
