<?php

namespace Tests\Unit;

use App\Jobs\ProcessWebhookJob;
use App\Models\Bank;
use App\Models\Client;
use App\Services\Parsers\BankParserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    private Bank $bank;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bank = Bank::factory()->create(['name' => 'foodics']);
        $this->client = Client::factory()->create();
    }

    public function test_logs_warning_when_some_lines_are_malformed(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Some malformed lines in webhook payload.'
                    && $context['bank_id'] === $this->bank->id
                    && $context['client_id'] === $this->client->id
                    && $context['total_lines'] === 3
                    && $context['successful_lines'] === 2
                    && $context['failed_lines'] === 1;
            });

        $body = "20250615156,50#REF001#\nBADLINE\n20250616200,00#REF002#";
        $job = new ProcessWebhookJob($body, $this->client->id, $this->bank->id);
        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_logs_error_when_all_lines_are_malformed(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'High malformed line ratio in webhook payload.'
                    && $context['bank_id'] === $this->bank->id
                    && $context['client_id'] === $this->client->id
                    && $context['failed_lines'] === 2
                    && $context['successful_lines'] === 0;
            });

        $body = "BADLINE1\nBADLINE2";
        $job = new ProcessWebhookJob($body, $this->client->id, $this->bank->id);
        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_still_inserts_valid_transactions_when_some_lines_fail(): void
    {
        Log::shouldReceive('warning')->once()->withAnyArgs();

        $body = "20250615156,50#REF001#note/payment\nBADLINE\n20250616200,00#REF002#";
        $job = new ProcessWebhookJob($body, $this->client->id, $this->bank->id);
        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseHas('transactions', [
            'reference' => 'REF001',
            'bank_id' => $this->bank->id,
            'client_id' => $this->client->id,
        ]);
        $this->assertDatabaseHas('transactions', [
            'reference' => 'REF002',
            'bank_id' => $this->bank->id,
            'client_id' => $this->client->id,
        ]);
        $this->assertDatabaseCount('transactions', 2);
    }
}
