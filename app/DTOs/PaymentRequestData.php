<?php

namespace App\DTOs;

use App\Http\Requests\TransferRequest;

class PaymentRequestData
{
    public function __construct(
        public readonly string $reference,
        public readonly string $date,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $senderAccount,
        public readonly string $receiverBankCode,
        public readonly string $receiverAccount,
        public readonly string $beneficiaryName,
        public readonly array $notes = [],
        public readonly int $paymentType = 99,
        public readonly string $chargeDetails = 'SHA',
    ) {}

    public static function fromRequest(TransferRequest $request): self
    {
        return new self(
            reference: $request->validated('reference'),
            date: $request->validated('date'),
            amount: $request->validated('amount'),
            currency: $request->validated('currency'),
            senderAccount: $request->validated('sender_account'),
            receiverBankCode: $request->validated('receiver_bank_code'),
            receiverAccount: $request->validated('receiver_account'),
            beneficiaryName: $request->validated('beneficiary_name'),
            notes: $request->validated('notes', []),
            paymentType: (int) $request->validated('payment_type', 99),
            chargeDetails: $request->validated('charge_details', 'SHA'),
        );
    }
}
