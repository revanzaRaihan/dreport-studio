<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix SSL certificate verification on Windows.
        // Points Guzzle at the downloaded Mozilla CA bundle.
        $certPath = ini_get('curl.cainfo');

        Http::globalOptions([
            'verify' => ($certPath && file_exists($certPath)) ? $certPath : false,
        ]);
    }
}
