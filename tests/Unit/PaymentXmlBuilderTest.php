<?php

namespace Tests\Unit;

use App\DTOs\PaymentRequestData;
use App\Services\PaymentXmlBuilder;
use PHPUnit\Framework\TestCase;

class PaymentXmlBuilderTest extends TestCase
{
    private PaymentXmlBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new PaymentXmlBuilder;
    }

    private function makePaymentData(array $overrides = []): PaymentRequestData
    {
        return new PaymentRequestData(
            reference: $overrides['reference'] ?? 'REF001',
            date: $overrides['date'] ?? '2025-06-15',
            amount: $overrides['amount'] ?? '1500.00',
            currency: $overrides['currency'] ?? 'SAR',
            senderAccount: $overrides['senderAccount'] ?? 'SA1234567890',
            receiverBankCode: $overrides['receiverBankCode'] ?? 'RJHI',
            receiverAccount: $overrides['receiverAccount'] ?? 'SA0987654321',
            beneficiaryName: $overrides['beneficiaryName'] ?? 'John Doe',
            notes: $overrides['notes'] ?? [],
            paymentType: $overrides['paymentType'] ?? 99,
            chargeDetails: $overrides['chargeDetails'] ?? 'SHA',
        );
    }

    public function test_generates_full_xml_with_required_fields(): void
    {
        $data = $this->makePaymentData();
        $xml = $this->builder->build($data);

        $this->assertStringContainsString('<PaymentRequestMessage>', $xml);
        $this->assertStringContainsString('<TransferInfo>', $xml);
        $this->assertStringContainsString('<Reference>REF001</Reference>', $xml);
        $this->assertStringContainsString('<Date>2025-06-15</Date>', $xml);
        $this->assertStringContainsString('<Amount>1500.00</Amount>', $xml);
        $this->assertStringContainsString('<Currency>SAR</Currency>', $xml);
        $this->assertStringContainsString('<SenderInfo>', $xml);
        $this->assertStringContainsString('<ReceiverInfo>', $xml);
        $this->assertStringContainsString('<BeneficiaryName>John Doe</BeneficiaryName>', $xml);

        // Verify nesting via XPath
        $dom = new \DOMDocument;
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);
        $this->assertEquals('SA1234567890', $xpath->query('//SenderInfo/AccountNumber')->item(0)->textContent);
        $this->assertEquals('RJHI', $xpath->query('//ReceiverInfo/BankCode')->item(0)->textContent);
        $this->assertEquals('SA0987654321', $xpath->query('//ReceiverInfo/AccountNumber')->item(0)->textContent);
    }

    public function test_omits_notes_when_empty(): void
    {
        $data = $this->makePaymentData(['notes' => []]);
        $xml = $this->builder->build($data);

        $this->assertStringNotContainsString('<Notes>', $xml);
    }

    public function test_includes_notes_when_present(): void
    {
        $data = $this->makePaymentData(['notes' => ['Payment for invoice', 'Urgent']]);
        $xml = $this->builder->build($data);

        $this->assertStringContainsString('<Notes>', $xml);
        $this->assertStringContainsString('<Note>Payment for invoice</Note>', $xml);
        $this->assertStringContainsString('<Note>Urgent</Note>', $xml);
    }

    public function test_omits_payment_type_when_99(): void
    {
        $data = $this->makePaymentData(['paymentType' => 99]);
        $xml = $this->builder->build($data);

        $this->assertStringNotContainsString('<PaymentType>', $xml);
    }

    public function test_includes_payment_type_when_not_99(): void
    {
        $data = $this->makePaymentData(['paymentType' => 1]);
        $xml = $this->builder->build($data);

        $this->assertStringContainsString('<PaymentType>1</PaymentType>', $xml);
    }

    public function test_omits_charge_details_when_sha(): void
    {
        $data = $this->makePaymentData(['chargeDetails' => 'SHA']);
        $xml = $this->builder->build($data);

        $this->assertStringNotContainsString('<ChargeDetails>', $xml);
    }

    public function test_includes_charge_details_when_not_sha(): void
    {
        $data = $this->makePaymentData(['chargeDetails' => 'OUR']);
        $xml = $this->builder->build($data);

        $this->assertStringContainsString('<ChargeDetails>OUR</ChargeDetails>', $xml);
    }

    public function test_generates_valid_xml(): void
    {
        $data = $this->makePaymentData([
            'notes' => ['Test note'],
            'paymentType' => 1,
            'chargeDetails' => 'OUR',
        ]);
        $xml = $this->builder->build($data);

        $dom = new \DOMDocument;
        $this->assertTrue($dom->loadXML($xml));
    }
}
