<?php

namespace App\Services\Parsers;

use App\Contracts\BankParserInterface;
use App\DTOs\TransactionData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractBankParser implements BankParserInterface
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? $this->resolveLogger();
    }

    /** @return Collection<int, TransactionData> */
    public function parse(string $rawBody): Collection
    {
        $lines = $this->splitLines($rawBody);

        return collect($lines)
            ->map(function (string $line) {
                try {
                    return $this->parseLine($line);
                } catch (\Throwable $e) {
                    $this->logger->warning('Skipping malformed line in webhook payload.', [
                        'parser' => static::class,
                        'line' => mb_substr($line, 0, 200),
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            })
            ->filter();
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

    private function resolveLogger(): LoggerInterface
    {
        if (function_exists('app') && app()->bound(LoggerInterface::class)) {
            return app(LoggerInterface::class);
        }

        return new NullLogger;
    }
}
