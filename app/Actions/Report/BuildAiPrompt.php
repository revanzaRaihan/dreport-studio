<?php

namespace App\Actions\Report;

use App\Models\Student;
use App\Models\DatasetEntry;
use Carbon\Carbon;

class BuildAiPrompt
{
    /**
     * Build the prompt string for the AI model using few-shot training.
     */
    public function execute(
        Student $student,
        string $meetingNo,
        string $dateVal,
        string $materi,
        string $behavior,
        string $category,
        string $language = 'id'
    ): string {
        // Load the last 12 dataset entries for few-shot examples by section_type
        $overviewEntries = DatasetEntry::where('language', $language)
            ->where('section_type', 'overview')
            ->latest()
            ->take(12)
            ->get()
            ->reverse();

        $teachersNoteEntries = DatasetEntry::where('language', $language)
            ->where('section_type', 'teachers_note')
            ->latest()
            ->take(12)
            ->get()
            ->reverse();

        $parentNoteEntries = DatasetEntry::where('language', $language)
            ->where('section_type', 'parent_note')
            ->latest()
            ->take(12)
            ->get()
            ->reverse();

        // Load the last 12 recommendation examples by category
        $recommendationEntries = \App\Models\RecommendationDataset::where('language', $language)
            ->where('category', $category)
            ->latest()
            ->take(12)
            ->get()
            ->reverse();

        $overviewExamples = $this->formatExamples($overviewEntries);
        $teachersNoteExamples = $this->formatExamples($teachersNoteEntries);
        $parentNoteExamples = $this->formatExamples($parentNoteEntries);
        $recommendationExamples = $this->formatExamples($recommendationEntries);

        // Format date to DD/MM/YYYY
        $formattedDate = Carbon::parse($dateVal)->format('d/m/Y');

        if ($language === 'en') {
            return "You are helping a private tutor write a daily student progress report in English.
Your task: generate a JSON response filling 4 sections ('overview', 'teachersNote', 'trainingRecommendation', 'parentNote') based on today's inputs and matching the writing style, diction, and tone of the tutor's examples.

=== WRITING STYLE REFERENCE: OVERVIEW ===
{$overviewExamples}
=== WRITING STYLE REFERENCE: TEACHER'S NOTE ===
{$teachersNoteExamples}
=== WRITING STYLE REFERENCE: TRAINING RECOMMENDATION (Category: {$category}) ===
{$recommendationExamples}
=== WRITING STYLE REFERENCE: NOTE FOR PARENTS ===
{$parentNoteExamples}

Now, generate the content using today's data:
- Date: {$formattedDate}
- Subject: {$student->subject}
- Meeting number: {$meetingNo}
- Student name: {$student->name}
- Lesson material today: {$materi}
- Student behavior today: {$behavior}

Output Rules:
1. Return ONLY a valid JSON object. No explanation, no markdown blocks.
2. The JSON keys MUST be exactly: 'overview', 'teachersNote', 'trainingRecommendation', 'parentNote'.
3. Do NOT start the content of any section with dates, student name, or course titles (e.g. do not prepend with 'Renziro Lesson X' or '14/07/2026') as this layout metadata will be prepended programmatically.
4. Copy the tone and structure of the examples closely.";
        }

        return "Kamu membantu seorang guru les privat menulis laporan progres harian murid dalam Bahasa Indonesia.
Tugasmu: menghasilkan respon JSON dengan 4 section ('overview', 'teachersNote', 'trainingRecommendation', 'parentNote') berdasarkan data input hari ini dan meniru gaya penulisan, diksi, dan nada dari contoh-contoh yang diberikan.

=== REFERENSI GAYA PENULISAN: OVERVIEW ===
{$overviewExamples}
=== REFERENSI GAYA PENULISAN: CATATAN GURU (TEACHER'S NOTE) ===
{$teachersNoteExamples}
=== REFERENSI GAYA PENULISAN: REKOMENDASI LATIHAN (Kategori: {$category}) ===
{$recommendationExamples}
=== REFERENSI GAYA PENULISAN: CATATAN UNTUK ORANG TUA ===
{$parentNoteExamples}

Sekarang, buat isi laporan dengan data hari ini:
- Tanggal: {$formattedDate}
- Mata pelajaran / kelas: {$student->subject}
- Meeting ke: {$meetingNo}
- Nama murid: {$student->name}
- Materi hari ini: {$materi}
- Behavior/observasi guru terhadap murid: {$behavior}

Aturan Output:
1. Kembalikan HANYA objek JSON valid. Jangan ada penjelasan tambahan atau blok markdown.
2. Key dari JSON HARUS tepat: 'overview', 'teachersNote', 'trainingRecommendation', 'parentNote'.
3. Jangan mengawali isi teks bagian mana pun dengan tanggal, nama murid, atau nama kelas (misal, jangan diawali dengan 'Renziro Lesson X' atau '14/07/2026') karena layout meta ini akan disusun otomatis oleh kode program.
4. Tiru gaya pembawaan dan gaya bahasa dari contoh referensi dengan erat.";
    }

    /**
     * Format list of entries as string.
     */
    private function formatExamples($entries): string
    {
        if ($entries->isEmpty()) {
            return "(Belum ada contoh gaya penulisan untuk bagian ini. Tulis dengan bahasa yang hangat, profesional, dan mengayomi.)\n\n";
        }

        $examples = '';
        $index = 1;
        foreach ($entries as $entry) {
            $examples .= "Contoh {$index}:\n" . trim($entry->body) . "\n\n";
            $index++;
        }
        return $examples;
    }
}
