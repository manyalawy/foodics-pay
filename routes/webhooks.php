<?php

use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks', [WebhookController::class, 'handle'])
    ->middleware(['verify.bank_webhook', 'throttle:webhooks']);
