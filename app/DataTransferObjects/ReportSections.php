<?php

namespace App\DataTransferObjects;

class ReportSections
{
    public function __construct(
        public string $overview,
        public string $teachersNote,
        public string $trainingRecommendation,
        public string $parentNote,
        public string $lessonCompleted = ''
    ) {}

    /**
     * Parse structured JSON from AI and handle camelCase/snake_case keys.
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode(trim($json), true);

        // Fallback: If JSON decoding fails, try to repair or extract with regex.
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $data = self::fallbackParse($json);
        }

        $overview = $data['overview'] ?? '';
        $teachersNote = $data['teachersNote'] ?? $data['teachers_note'] ?? '';
        $trainingRecommendation = $data['trainingRecommendation'] ?? $data['training_recommendation'] ?? '';
        $parentNote = $data['parentNote'] ?? $data['parent_note'] ?? '';
        $lessonCompleted = $data['lessonCompleted'] ?? $data['lesson_completed'] ?? '';

        return new self(
            trim($overview),
            trim($teachersNote),
            trim($trainingRecommendation),
            trim($parentNote),
            trim($lessonCompleted)
        );
    }

    /**
     * Helper to convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'overview' => $this->overview,
            'teachersNote' => $this->teachersNote,
            'trainingRecommendation' => $this->trainingRecommendation,
            'parentNote' => $this->parentNote,
            'lessonCompleted' => $this->lessonCompleted,
        ];
    }

    /**
     * Crude regex-based parser for when AI output isn't perfect JSON.
     */
    private static function fallbackParse(string $rawText): array
    {
        $result = [];
        $keys = ['overview', 'teachersNote', 'teachers_note', 'trainingRecommendation', 'training_recommendation', 'parentNote', 'parent_note'];
        
        foreach ($keys as $key) {
            $pattern = '/"' . preg_quote($key, '/') . '"\s*:\s*"([^"]+)"/i';
            if (preg_match($pattern, $rawText, $matches)) {
                $result[$key] = $matches[1];
            } else {
                $patternSingle = '/\'' . preg_quote($key, '/') . '\'\s*:\s*\'([^\'\s]+)\'/i';
                if (preg_match($patternSingle, $rawText, $matches)) {
                    $result[$key] = $matches[1];
                }
            }
        }

        if (empty($result)) {
            $lines = array_filter(array_map('trim', explode("\n", $rawText)));
            $lines = array_values($lines);
            
            $result['overview'] = $lines[0] ?? $rawText;
            $result['teachersNote'] = $lines[1] ?? '';
            $result['trainingRecommendation'] = $lines[2] ?? '';
            $result['parentNote'] = $lines[3] ?? '';
        }

        return $result;
    }
}