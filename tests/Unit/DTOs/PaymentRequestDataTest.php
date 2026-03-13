<?php

namespace Tests\Unit\DTOs;

use App\DTOs\PaymentRequestData;
use PHPUnit\Framework\TestCase;

class PaymentRequestDataTest extends TestCase
{
    public function test_constructs_with_all_fields(): void
    {
        $dto = new PaymentRequestData(
            reference: 'REF001',
            date: '2025-06-15',
            amount: '1500.00',
            currency: 'SAR',
            senderAccount: 'SA1234567890',
            receiverBankCode: 'RJHI',
            receiverAccount: 'SA0987654321',
            beneficiaryName: 'John Doe',
            notes: ['Test note'],
            paymentType: 1,
            chargeDetails: 'OUR',
        );

        $this->assertEquals('REF001', $dto->reference);
        $this->assertEquals('2025-06-15', $dto->date);
        $this->assertEquals('1500.00', $dto->amount);
        $this->assertEquals('SAR', $dto->currency);
        $this->assertEquals('SA1234567890', $dto->senderAccount);
        $this->assertEquals('RJHI', $dto->receiverBankCode);
        $this->assertEquals('SA0987654321', $dto->receiverAccount);
        $this->assertEquals('John Doe', $dto->beneficiaryName);
        $this->assertEquals(['Test note'], $dto->notes);
        $this->assertEquals(1, $dto->paymentType);
        $this->assertEquals('OUR', $dto->chargeDetails);
    }

    public function test_has_defaults_for_optional_fields(): void
    {
        $dto = new PaymentRequestData(
            reference: 'REF001',
            date: '2025-06-15',
            amount: '1500.00',
            currency: 'SAR',
            senderAccount: 'SA1234567890',
            receiverBankCode: 'RJHI',
            receiverAccount: 'SA0987654321',
            beneficiaryName: 'John Doe',
        );

        $this->assertEmpty($dto->notes);
        $this->assertEquals(99, $dto->paymentType);
        $this->assertEquals('SHA', $dto->chargeDetails);
    }
}
