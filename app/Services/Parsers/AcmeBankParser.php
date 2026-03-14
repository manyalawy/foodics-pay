<?php

namespace App\Services\Parsers;

use App\DTOs\TransactionData;

/**
 * Parses Acme Bank webhook format.
 *
 * Line format: {amount_with_comma}//{reference}//{YYYYMMDD}
 * Example:     156,50//202506159000001//20250615
 */
class AcmeBankParser extends AbstractBankParser
{
    protected function parseLine(string $line): TransactionData
    {
        $parts = explode('//', $line);

        if (count($parts) < 3) {
            throw new \InvalidArgumentException('Line must contain 3 segments separated by //');
        }

        $amount = str_replace(',', '.', $parts[0]);
        $this->validateAmount($amount);

        $reference = $parts[1];
        $date = $this->validateDate($parts[2]);

        return new TransactionData(
            reference: $reference,
            amount: $amount,
            date: $date,
        );
    }
}
