<?php

use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::post('/transfers', [TransferController::class, 'store']);
