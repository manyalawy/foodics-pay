<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class IngestionController extends Controller
{
    public function pause(): JsonResponse
    {
        Cache::put('ingestion_paused', true);

        return response()->json(['message' => 'Ingestion paused.']);
    }

    public function resume(): JsonResponse
    {
        Cache::forget('ingestion_paused');

        return response()->json(['message' => 'Ingestion resumed.']);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'paused' => (bool) Cache::get('ingestion_paused', false),
        ]);
    }
}
