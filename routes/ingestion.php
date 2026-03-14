<?php

use App\Http\Controllers\Api\IngestionController;
use Illuminate\Support\Facades\Route;

Route::prefix('ingestion')->middleware('throttle:ingestion')->group(function () {
    Route::post('/pause', [IngestionController::class, 'pause']);
    Route::post('/resume', [IngestionController::class, 'resume']);
    Route::get('/status', [IngestionController::class, 'status']);
});
