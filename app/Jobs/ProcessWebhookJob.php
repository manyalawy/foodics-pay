<?php

namespace App\Jobs;

use App\Models\Bank;
use App\Services\Parsers\BankParserFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly string $rawBody,
        private readonly int $clientId,
        private readonly int $bankId,
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(BankParserFactory $parserFactory): void
    {
        if (Cache::get('ingestion_paused')) {
            $this->release(30);

            return;
        }

        $bank = Bank::findOrFail($this->bankId);
        $parser = $parserFactory->make($bank->name);
        $transactions = $parser->parse($this->rawBody);

        $transactions->chunk(500)->each(function ($chunk) {
            $records = $chunk->map(fn ($tx) => [
                'client_id' => $this->clientId,
                'bank_id' => $this->bankId,
                'reference' => $tx->reference,
                'amount' => $tx->amount,
                'date' => $tx->date->toDateString(),
                'metadata' => $tx->metadata ? json_encode($tx->metadata) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            DB::table('transactions')->insertOrIgnore($records);
        });
    }
}
