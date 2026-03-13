<?php

namespace App\Http\Controllers\Api;

use App\DTOs\PaymentRequestData;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Services\PaymentXmlBuilder;
use Illuminate\Http\Response;

class TransferController extends Controller
{
    public function store(TransferRequest $request, PaymentXmlBuilder $builder): Response
    {
        $paymentData = PaymentRequestData::fromRequest($request);
        $xml = $builder->build($paymentData);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
