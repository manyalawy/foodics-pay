<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Test credentials:
     *
     * Foodics Bank API Key: foodics-api-key-2025
     * Acme Bank API Key:    acme-api-key-2025
     * Client Token:         client-webhook-token-2025
     */
    public function run(): void
    {
        $foodicsBank = Bank::create([
            'name' => 'foodics',
            'api_key_hash' => hash('sha256', 'foodics-api-key-2025'),
            'webhook_secret' => 'foodics-webhook-secret-2025',
        ]);

        $acmeBank = Bank::create([
            'name' => 'acme',
            'api_key_hash' => hash('sha256', 'acme-api-key-2025'),
            'webhook_secret' => 'acme-webhook-secret-2025',
        ]);

        $client = Client::create([
            'name' => 'Foodics Pay Demo Client',
            'webhook_token' => 'client-webhook-token-2025',
        ]);

        Transaction::create([
            'client_id' => $client->id,
            'bank_id' => $foodicsBank->id,
            'reference' => '202506159000001',
            'amount' => 156.50,
            'date' => '2025-06-15',
            'metadata' => ['note' => 'debt payment march', 'internal_reference' => 'A462JE81'],
        ]);

        Transaction::create([
            'client_id' => $client->id,
            'bank_id' => $foodicsBank->id,
            'reference' => '202506159000002',
            'amount' => 9000.00,
            'date' => '2025-06-15',
            'metadata' => ['note' => 'salary payment'],
        ]);

        Transaction::create([
            'client_id' => $client->id,
            'bank_id' => $acmeBank->id,
            'reference' => '202506159000003',
            'amount' => 250.75,
            'date' => '2025-06-15',
        ]);
    }
}