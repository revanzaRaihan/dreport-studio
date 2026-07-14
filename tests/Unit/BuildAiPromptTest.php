<?php

namespace Tests\Unit;

use App\Actions\Report\BuildAiPrompt;
use App\Models\Student;
use App\Models\DatasetEntry;
use App\Models\RecommendationDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildAiPromptTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test prompt builder correctly includes student metadata and few-shot examples.
     */
    public function test_prompt_builder_includes_all_metadata_and_examples(): void
    {
        $student = Student::create([
            'name' => 'Fadhil',
            'subject' => 'Python Basics',
            'meeting_count' => 2,
        ]);

        DatasetEntry::create([
            'body' => 'Contoh gaya penulisan overview Fadhil.',
            'language' => 'id',
            'section_type' => 'overview',
        ]);

        DatasetEntry::create([
            'body' => 'Contoh gaya penulisan note guru.',
            'language' => 'id',
            'section_type' => 'teachers_note',
        ]);

        RecommendationDataset::create([
            'body' => 'Contoh rekomendasi latihan logika.',
            'language' => 'id',
            'category' => 'logika_terstruktur',
        ]);

        $builder = new BuildAiPrompt();
        $prompt = $builder->execute(
            $student,
            '3',
            '2026-07-14',
            'Variables and Types',
            'Paham konsep dasar tapi agak lambat mengetik',
            'logika_terstruktur',
            'id'
        );

        $this->assertStringContainsString('Fadhil', $prompt);
        $this->assertStringContainsString('Python Basics', $prompt);
        $this->assertStringContainsString('Variables and Types', $prompt);
        $this->assertStringContainsString('Contoh gaya penulisan overview Fadhil.', $prompt);
        $this->assertStringContainsString('Contoh gaya penulisan note guru.', $prompt);
        $this->assertStringContainsString('Contoh rekomendasi latihan logika.', $prompt);
    }
}
