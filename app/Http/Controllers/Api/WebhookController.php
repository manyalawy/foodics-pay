<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWebhookJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $bank = $request->attributes->get('bank');
        $client = $request->attributes->get('client');

        ProcessWebhookJob::dispatch(
            $request->getContent(),
            $client->id,
            $bank->id,
        );

        return response()->json(['message' => 'Webhook received.'], 202);
    }
}
