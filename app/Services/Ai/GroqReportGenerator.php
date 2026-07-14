<?php

namespace App\Services\Ai;

use App\Models\AppSetting;
use App\DataTransferObjects\ReportSections;
use Illuminate\Support\Facades\Http;
use Exception;

class GroqReportGenerator implements AiReportGeneratorInterface
{
    /**
     * Generate content using Groq Chat Completions API.
     */
    public function generate(string $prompt): ReportSections
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
                'temperature' => 0.7,
                'response_format' => [
                    'type' => 'json_object'
                ]
            ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? 'HTTP error ' . $response->status();
            throw new Exception('Groq API Error: ' . $errorMsg);
        }

        $text = $response->json('choices.0.message.content');

        if (!$text) {
            throw new Exception('Groq API returned an empty response.');
        }

        return ReportSections::fromJson($text);
    }

    /**
     * Classify behavior and materi into a recommendation category using Groq.
     */
    public function classifyCategory(string $behavior, string $materi): string
    {
        $apiKey = AppSetting::getValue('ai_api_key') ?: env('AI_API_KEY');
        $model = AppSetting::getValue('ai_model') ?: 'llama3-8b-8192';

        if (!$apiKey) {
            return 'coding_dasar'; // Fallback
        }

        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $prompt = "Klasifikasikan materi dan behavior berikut ke dalam salah satu kategori: kreativitas, logika_terstruktur, eksperimen, coding_dasar.\n\nMateri: {$materi}\nBehavior: {$behavior}\n\nKembalikan HANYA JSON dengan key 'category' dan value nama kategori tersebut (contoh: {\"category\": \"logika_terstruktur\"}). Jangan ada penjelasan lain.";

        try {
            $response = Http::timeout(60)
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
                    'temperature' => 0.2,
                    'response_format' => [
                        'type' => 'json_object'
                    ]
                ]);

            if ($response->failed()) {
                return 'coding_dasar';
            }

            $text = $response->json('choices.0.message.content');
            if (!$text) {
                return 'coding_dasar';
            }

            $data = json_decode(trim($text), true);
            return $data['category'] ?? 'coding_dasar';
        } catch (\Exception $e) {
            return 'coding_dasar';
        }
    }
}
