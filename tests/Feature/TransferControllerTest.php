<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'reference' => 'REF001',
            'date' => '2025-06-15',
            'amount' => 1500.00,
            'currency' => 'SAR',
            'sender_account' => 'SA1234567890',
            'receiver_bank_code' => 'RJHI',
            'receiver_account' => 'SA0987654321',
            'beneficiary_name' => 'John Doe',
        ], $overrides);
    }

    public function test_returns_xml_response(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload());

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $this->assertStringContainsString('<PaymentRequestMessage>', $response->getContent());
        $this->assertStringContainsString('<Reference>REF001</Reference>', $response->getContent());
    }

    public function test_validation_errors_for_missing_fields(): void
    {
        $response = $this->postJson('/api/transfers', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'reference', 'date', 'amount', 'currency',
            'sender_account', 'receiver_bank_code',
            'receiver_account', 'beneficiary_name',
        ]);
    }

    public function test_includes_notes_in_xml(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload([
            'notes' => ['Payment for invoice 123'],
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('<Notes>', $response->getContent());
        $this->assertStringContainsString('<Note>Payment for invoice 123</Note>', $response->getContent());
    }

    public function test_omits_notes_when_not_provided(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload());

        $this->assertStringNotContainsString('<Notes>', $response->getContent());
    }

    public function test_omits_payment_type_when_default(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload([
            'payment_type' => 99,
        ]));

        $this->assertStringNotContainsString('<PaymentType>', $response->getContent());
    }

    public function test_includes_payment_type_when_not_default(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload([
            'payment_type' => 1,
        ]));

        $this->assertStringContainsString('<PaymentType>1</PaymentType>', $response->getContent());
    }

    public function test_omits_charge_details_when_sha(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload([
            'charge_details' => 'SHA',
        ]));

        $this->assertStringNotContainsString('<ChargeDetails>', $response->getContent());
    }

    public function test_includes_charge_details_when_not_sha(): void
    {
        $response = $this->postJson('/api/transfers', $this->validPayload([
            'charge_details' => 'OUR',
        ]));

        $this->assertStringContainsString('<ChargeDetails>OUR</ChargeDetails>', $response->getContent());
    }
}
