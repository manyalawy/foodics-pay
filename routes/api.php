<?php

use App\Http\Controllers\Api\IngestionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks', [WebhookController::class, 'handle'])
    ->middleware('verify.bank_webhook');

Route::post('/transfers', [TransferController::class, 'store']);

Route::prefix('ingestion')->group(function () {
    Route::post('/pause', [IngestionController::class, 'pause']);
    Route::post('/resume', [IngestionController::class, 'resume']);
    Route::get('/status', [IngestionController::class, 'status']);
});
