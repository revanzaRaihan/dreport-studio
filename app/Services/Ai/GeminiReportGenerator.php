<?php

namespace App\Services\Ai;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Exception;

class GeminiReportGenerator implements AiReportGeneratorInterface
{
    /**
     * Generate content using Google Gemini API.
     */
    public function generate(string $prompt): string
    {
        $apiKey = AppSetting::getValue('ai_api_key') ?: env('AI_API_KEY');
        $model = AppSetting::getValue('ai_model', 'gemini-2.5-flash');

        if (!$apiKey) {
            throw new Exception('Gemini API key is not configured in Settings.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? 'HTTP error ' . $response->status();
            throw new Exception('Gemini API Error: ' . $errorMsg);
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (!$text) {
            throw new Exception('Gemini API returned an empty response.');
        }

        return trim($text);
    }
}
