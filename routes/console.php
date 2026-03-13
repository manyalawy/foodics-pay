<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

Artisan::command('ingestion:pause', function () {
    Cache::put('ingestion_paused', true);
    $this->info('Ingestion paused.');
})->purpose('Pause webhook ingestion processing');

Artisan::command('ingestion:resume', function () {
    Cache::forget('ingestion_paused');
    $this->info('Ingestion resumed.');
})->purpose('Resume webhook ingestion processing');
