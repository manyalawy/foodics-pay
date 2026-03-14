<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private Bank $bank;

    private Client $client;

    private string $apiKey = 'test-api-key-12345';

    private string $clientToken = 'test-client-token-12345';

    protected function setUp(): void
    {
        parent::setUp();

        $this->bank = Bank::factory()->create([
            'name' => 'foodics',
            'api_key_hash' => hash('sha256', $this->apiKey),
            'webhook_secret' => 'test-webhook-secret',
        ]);

        $this->client = Client::factory()->create([
            'webhook_token_hash' => hash('sha256', $this->clientToken),
        ]);
    }

    private function sendWebhook(string $body, array $headers = []): TestResponse
    {
        $defaultHeaders = [
            'Authorization' => "Bearer {$this->apiKey}",
            'X-Signature' => hash_hmac('sha256', $body, 'test-webhook-secret'),
            'X-Client-Token' => $this->clientToken,
        ];

        $mergedHeaders = array_merge($defaultHeaders, $headers);
        $server = $this->transformHeadersToServerVars($mergedHeaders);

        return $this->call('POST', '/api/webhooks', [], [], [], $server, $body);
    }

    public function test_returns_413_when_body_exceeds_max_size(): void
    {
        config()->set('webhook.max_body_size', 100);

        $body = str_repeat('a', 101);

        $this->sendWebhook($body)->assertStatus(413)
            ->assertJson(['error' => 'Payload too large.']);
    }

    public function test_accepts_body_at_exactly_max_size(): void
    {
        config()->set('webhook.max_body_size', 100);

        $body = str_repeat('a', 100);

        $response = $this->sendWebhook($body);

        $this->assertNotEquals(413, $response->getStatusCode());
    }

    public function test_returns_401_for_missing_api_key(): void
    {
        $server = $this->transformHeadersToServerVars([]);

        $this->call('POST', '/api/webhooks', [], [], [], $server, 'test')
            ->assertStatus(401);
    }

    public function test_returns_401_for_invalid_api_key(): void
    {
        $body = 'test';

        $this->sendWebhook($body, [
            'Authorization' => 'Bearer invalid-key',
        ])->assertStatus(401);
    }

    public function test_returns_403_for_invalid_signature(): void
    {
        $body = 'test';

        $this->sendWebhook($body, [
            'X-Signature' => 'invalid-signature',
        ])->assertStatus(403);
    }

    public function test_returns_401_for_invalid_client_token(): void
    {
        $body = 'test';

        $this->sendWebhook($body, [
            'X-Client-Token' => 'invalid-token',
        ])->assertStatus(401);
    }

    public function test_processes_foodics_webhook_successfully(): void
    {
        $body = '20250615156,50#202506159000001#note/debt payment march';

        $response = $this->sendWebhook($body);

        $response->assertStatus(202);
        $this->assertDatabaseHas('transactions', [
            'bank_id' => $this->bank->id,
            'client_id' => $this->client->id,
            'reference' => '202506159000001',
            'amount' => '156.50',
        ]);
    }

    public function test_handles_duplicate_transactions_idempotently(): void
    {
        $body = '20250615156,50#202506159000001#note/payment';

        $this->sendWebhook($body);
        $this->sendWebhook($body);

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_processes_acme_webhook_successfully(): void
    {
        $acmeBank = Bank::factory()->create([
            'name' => 'acme',
            'api_key_hash' => hash('sha256', 'acme-api-key'),
            'webhook_secret' => 'acme-secret',
        ]);

        $body = '156,50//202506159000001//20250615';
        $signature = hash_hmac('sha256', $body, 'acme-secret');

        $server = $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer acme-api-key',
            'X-Signature' => $signature,
            'X-Client-Token' => $this->clientToken,
        ]);

        $response = $this->call('POST', '/api/webhooks', [], [], [], $server, $body);

        $response->assertStatus(202);
        $this->assertDatabaseHas('transactions', [
            'bank_id' => $acmeBank->id,
            'reference' => '202506159000001',
        ]);
    }
}
