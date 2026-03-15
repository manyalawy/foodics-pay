<?php

namespace Tests\Unit;

use App\Jobs\ProcessWebhookJob;
use App\Models\Bank;
use App\Models\Client;
use App\Services\Parsers\BankParserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessWebhookJobAtomicityTest extends TestCase
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

    public function test_no_partial_records_persist_when_insert_fails_mid_transaction(): void
    {
        $body = "20250615156,50#REF001#\n20250616200,00#REF002#";

        $job = new ProcessWebhookJob($body, $this->client->id, $this->bank->id);

        // Simulate a database failure after the first chunk by using a spy on DB::table
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \RuntimeException('Simulated DB failure'));

        // Allow other DB calls to pass through
        DB::makePartial();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated DB failure');

        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_all_records_persist_when_transaction_succeeds(): void
    {
        $body = "20250615156,50#REF001#\n20250616200,00#REF002#";

        $job = new ProcessWebhookJob($body, $this->client->id, $this->bank->id);
        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', ['reference' => 'REF001']);
        $this->assertDatabaseHas('transactions', ['reference' => 'REF002']);
    }
}
