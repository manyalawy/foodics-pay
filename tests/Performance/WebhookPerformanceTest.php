<?php

namespace Tests\Performance;

use App\Jobs\ProcessWebhookJob;
use App\Models\Bank;
use App\Models\Client;
use App\Services\Parsers\BankParserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_1000_transactions_under_2_seconds(): void
    {
        $bank = Bank::factory()->create(['name' => 'foodics']);
        $client = Client::factory()->create();

        $lines = [];
        for ($i = 1; $i <= 1000; $i++) {
            $ref = str_pad((string) $i, 10, '0', STR_PAD_LEFT);
            $lines[] = "20250615156,50#{$ref}#note/test";
        }
        $body = implode("\n", $lines);

        $start = microtime(true);

        $job = new ProcessWebhookJob($body, $client->id, $bank->id);
        $job->handle(app(BankParserFactory::class));

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(2.0, $elapsed, "Processing 1000 transactions took {$elapsed}s (expected < 2s)");
        $this->assertDatabaseCount('transactions', 1000);
    }

    public function test_1000_transactions_with_500_duplicates(): void
    {
        $bank = Bank::factory()->create(['name' => 'foodics']);
        $client = Client::factory()->create();

        // First batch: 1000 transactions
        $lines = [];
        for ($i = 1; $i <= 1000; $i++) {
            $ref = str_pad((string) $i, 10, '0', STR_PAD_LEFT);
            $lines[] = "20250615156,50#{$ref}#note/test";
        }
        $body = implode("\n", $lines);

        $job = new ProcessWebhookJob($body, $client->id, $bank->id);
        $job->handle(app(BankParserFactory::class));

        // Second batch: 500 duplicates + 500 new
        $lines = [];
        for ($i = 501; $i <= 1500; $i++) {
            $ref = str_pad((string) $i, 10, '0', STR_PAD_LEFT);
            $lines[] = "20250615200,00#{$ref}#note/test2";
        }
        $body = implode("\n", $lines);

        $job = new ProcessWebhookJob($body, $client->id, $bank->id);
        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseCount('transactions', 1500);
    }
}
