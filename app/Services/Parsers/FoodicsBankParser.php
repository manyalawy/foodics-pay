<?php

namespace App\Services\Parsers;

use App\DTOs\TransactionData;

/**
 * Parses Foodics Bank webhook format.
 *
 * Line format: {YYYYMMDD}{amount_with_comma}#{reference}#{key/value/key/value...}
 * Example:     20250615156,50#202506159000001#note/debt payment march/internal_reference/A462JE81
 */
class FoodicsBankParser extends AbstractBankParser
{
    protected function parseLine(string $line): TransactionData
    {
        $parts = explode('#', $line);

        if (count($parts) < 2) {
            throw new \InvalidArgumentException('Line must contain at least 2 segments separated by #');
        }

        $dateAmount = $parts[0];
        $reference = $parts[1];
        $metadataRaw = $parts[2] ?? '';

        if (strlen($dateAmount) < 9) {
            throw new \InvalidArgumentException('Date+amount segment too short');
        }

        $date = $this->validateDate(substr($dateAmount, 0, 8));
        $amount = str_replace(',', '.', substr($dateAmount, 8));
        $this->validateAmount($amount);

        $metadata = $this->parseMetadata($metadataRaw);

        return new TransactionData(
            reference: $reference,
            amount: $amount,
            date: $date,
            metadata: $metadata,
        );
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
