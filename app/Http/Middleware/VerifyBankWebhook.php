<?php

namespace App\Http\Middleware;

use App\Models\Bank;
use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class VerifyBankWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $bank = $this->authenticateBank($request);
        if (! $bank) {
            return response()->json(['error' => 'Invalid API key.'], 401);
        }

        if (! $this->verifySignature($request, $bank)) {
            return response()->json(['error' => 'Invalid signature.'], 403);
        }

        $client = $this->resolveClient($request);
        if (! $client) {
            return response()->json(['error' => 'Invalid client token.'], 401);
        }

        $request->attributes->set('bank', $bank);
        $request->attributes->set('client', $client);

        return $next($request);
    }

    private function authenticateBank(Request $request): ?Bank
    {
        $apiKey = $request->bearerToken();
        if (! $apiKey) {
            return null;
        }

        $hash = hash('sha256', $apiKey);

        return Cache::remember(
            "bank:api_key:{$hash}",
            now()->addMinutes(5),
            fn () => Bank::where('api_key_hash', $hash)->first()
        );
    }

    private function verifySignature(Request $request, Bank $bank): bool
    {
        $signature = $request->header('X-Signature');
        if (! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $bank->webhook_secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function resolveClient(Request $request): ?Client
    {
        $token = $request->header('X-Client-Token');
        if (! $token) {
            return null;
        }

        return Cache::remember(
            "client:token:{$token}",
            now()->addMinutes(5),
            fn () => Client::where('webhook_token', $token)->first()
        );
    }
}
