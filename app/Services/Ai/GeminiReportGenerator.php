<?php

namespace App\Services\Ai;

use App\Models\AppSetting;
use App\DataTransferObjects\ReportSections;
use Illuminate\Support\Facades\Http;
use Exception;

class GeminiReportGenerator implements AiReportGeneratorInterface
{
    /**
     * Generate content using Google Gemini API.
     */
    public function generate(string $prompt): ReportSections
    {
        $apiKey = AppSetting::getValue('ai_api_key') ?: env('AI_API_KEY');
        $model = AppSetting::getValue('ai_model', 'gemini-2.5-flash');

        if (!$apiKey) {
            throw new Exception('Gemini API key is not configured in Settings.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(120)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'object',
                        'properties' => [
                            'overview' => ['type' => 'string'],
                            'teachersNote' => ['type' => 'string'],
                            'trainingRecommendation' => ['type' => 'string'],
                            'parentNote' => ['type' => 'string'],
                            'lessonCompleted' => ['type' => 'string'],
                        ],
                        'required' => ['overview', 'teachersNote', 'trainingRecommendation', 'parentNote', 'lessonCompleted']
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

        return ReportSections::fromJson($text);
    }

    /**
     * Classify behavior and materi into a recommendation category using Gemini.
     */
    public function classifyCategory(string $behavior, string $materi): string
    {
        $apiKey = AppSetting::getValue('ai_api_key') ?: env('AI_API_KEY');
        $model = AppSetting::getValue('ai_model', 'gemini-2.5-flash');

        if (!$apiKey) {
            return 'coding_dasar'; // Fallback
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $prompt = "Klasifikasikan materi dan behavior berikut ke dalam salah satu kategori: kreativitas, logika_terstruktur, eksperimen, coding_dasar.\n\nMateri: {$materi}\nBehavior: {$behavior}\n\nKembalikan HANYA JSON dengan key 'category' dan value nama kategori tersebut (contoh: {\"category\": \"logika_terstruktur\"}). Jangan ada penjelasan lain.";

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => [
                            'type' => 'object',
                            'properties' => [
                                'category' => [
                                    'type' => 'string',
                                    'enum' => ['kreativitas', 'logika_terstruktur', 'eksperimen', 'coding_dasar']
                                ]
                            ],
                            'required' => ['category']
                        ]
                    ]
                ]);

            if ($response->failed()) {
                return 'coding_dasar';
            }

            $text = $response->json('candidates.0.content.parts.0.text');
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
