<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string'],
            'date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'sender_account' => ['required', 'string'],
            'receiver_bank_code' => ['required', 'string'],
            'receiver_account' => ['required', 'string'],
            'beneficiary_name' => ['required', 'string'],
            'notes' => ['sometimes', 'array'],
            'notes.*' => ['string'],
            'payment_type' => ['sometimes', 'integer'],
            'charge_details' => ['sometimes', 'string'],
        ];
    }
}
