<?php

namespace App\Services\Parsers;

use App\Contracts\BankParserInterface;
use App\DTOs\ParseResult;
use App\DTOs\TransactionData;
use Carbon\CarbonImmutable;

abstract class AbstractBankParser implements BankParserInterface
{
    public function parse(string $rawBody): ParseResult
    {
        $lines = $this->splitLines($rawBody);
        $transactions = [];
        $errors = [];

        foreach ($lines as $index => $line) {
            try {
                $transactions[] = $this->parseLine($line);
            } catch (\Throwable $e) {
                $errors[] = [
                    'line' => $index + 1,
                    'input' => mb_substr($line, 0, 200),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return new ParseResult(collect($transactions), $errors);
    }

    /** @return list<string> */
    protected function splitLines(string $rawBody): array
    {
        return array_values(array_filter(explode("\n", $rawBody), fn (string $line) => trim($line) !== ''));
    }

    abstract protected function parseLine(string $line): TransactionData;

    protected function validateAmount(string $amount): void
    {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException("Invalid amount: {$amount}");
        }

        if ((float) $amount <= 0) {
            throw new \InvalidArgumentException("Amount must be positive: {$amount}");
        }
    }

    protected function validateDate(string $dateString, string $format = 'Ymd'): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat($format, $dateString);

        if (! $date || $date->format($format) !== $dateString) {
            throw new \InvalidArgumentException("Invalid date: {$dateString}");
        }

        return $date;
    }
}
