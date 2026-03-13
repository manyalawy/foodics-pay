<?php

namespace App\Services\Parsers;

use App\Contracts\BankParserInterface;
use App\DTOs\TransactionData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FoodicsBankParser implements BankParserInterface
{
    public function parse(string $rawBody): Collection
    {
        $lines = array_filter(explode("\n", $rawBody), fn (string $line) => trim($line) !== '');

        return collect($lines)->map(function (string $line) {
            $parts = explode('#', $line);

            $dateAmount = $parts[0];
            $reference = $parts[1];
            $metadataRaw = $parts[2] ?? '';

            $date = CarbonImmutable::createFromFormat('Ymd', substr($dateAmount, 0, 8));
            $amount = str_replace(',', '.', substr($dateAmount, 8));

            $metadata = $this->parseMetadata($metadataRaw);

            return new TransactionData(
                reference: $reference,
                amount: $amount,
                date: $date,
                metadata: $metadata,
            );
        });
    }

    private function parseMetadata(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $segments = explode('/', $raw);
        $metadata = [];

        for ($i = 0; $i < count($segments) - 1; $i += 2) {
            $key = $segments[$i];
            $value = $segments[$i + 1] ?? '';
            $metadata[$key] = $value;
        }

        return $metadata;
    }
}
