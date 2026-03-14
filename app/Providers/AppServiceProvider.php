<?php

namespace App\Providers;

use App\Services\Parsers\BankParserFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BankParserFactory::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(500)->by($request->bearerToken() ?: $request->ip());
        });

        RateLimiter::for('transfers', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('ingestion', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
