<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AppSetting;
use App\Services\Ai\AiReportGeneratorInterface;
use App\Services\Ai\GeminiReportGenerator;
use App\Services\Ai\GroqReportGenerator;
use Illuminate\Support\Facades\Schema;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AiReportGeneratorInterface::class, function ($app) {
            $provider = 'gemini';

            // Catch potential database connection issues during console commands / migrations
            try {
                if (Schema::hasTable('app_settings')) {
                    $provider = AppSetting::getValue('ai_provider', 'gemini');
                }
            } catch (\Exception $e) {
                $provider = env('AI_PROVIDER', 'gemini');
            }

            if (strtolower($provider) === 'groq') {
                return new GroqReportGenerator();
            }

            return new GeminiReportGenerator();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
