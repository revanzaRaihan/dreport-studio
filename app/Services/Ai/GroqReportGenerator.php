<?php

namespace App\Services\Ai;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Exception;

class GroqReportGenerator implements AiReportGeneratorInterface
{
    /**
     * Generate content using Groq Chat Completions API.
     */
    public function generate(string $prompt): string
    {
        $apiKey = AppSetting::getValue('ai_api_key') ?: env('AI_API_KEY');
        $model = AppSetting::getValue('ai_model') ?: 'llama3-8b-8192';

        if (!$apiKey) {
            throw new Exception('Groq API key is not configured in Settings.');
        }

        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7
            ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? 'HTTP error ' . $response->status();
            throw new Exception('Groq API Error: ' . $errorMsg);
        }

        $text = $response->json('choices.0.message.content');

        if (!$text) {
            throw new Exception('Groq API returned an empty response.');
        }

        return trim($text);
    }
}
