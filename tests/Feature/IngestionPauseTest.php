<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhookJob;
use App\Models\Bank;
use App\Models\Client;
use App\Services\Parsers\BankParserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IngestionPauseTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_released_when_ingestion_is_paused(): void
    {
        Queue::fake();
        Cache::put('ingestion_paused', true);

        $bank = Bank::factory()->create(['name' => 'foodics']);
        $client = Client::factory()->create();

        $job = new ProcessWebhookJob(
            '20250615156,50#REF001#',
            $client->id,
            $bank->id,
        );

        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseMissing('transactions', ['reference' => 'REF001']);
    }

    public function test_job_processes_when_ingestion_is_not_paused(): void
    {
        Cache::forget('ingestion_paused');

        $bank = Bank::factory()->create(['name' => 'foodics']);
        $client = Client::factory()->create();

        $job = new ProcessWebhookJob(
            '20250615156,50#REF001#',
            $client->id,
            $bank->id,
        );

        $job->handle(app(BankParserFactory::class));

        $this->assertDatabaseHas('transactions', [
            'reference' => 'REF001',
            'bank_id' => $bank->id,
            'client_id' => $client->id,
        ]);
    }
}
