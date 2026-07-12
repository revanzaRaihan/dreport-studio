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
    public function execute(Student $student, string $meetingNo, string $dateVal, string $materi, string $behavior, string $language = 'id'): string
    {
        // Load the last 12 dataset entries for few-shot examples of specified language
        $entries = DatasetEntry::where('language', $language)->latest()->take(12)->get()->reverse();

        $examples = '';
        $index = 1;
        foreach ($entries as $entry) {
            $examples .= "Contoh {$index}:\n" . trim($entry->body) . "\n\n";
            $index++;
        }

        // Format date to DD/MM/YYYY
        $formattedDate = Carbon::parse($dateVal)->format('d/m/Y');

        if ($language === 'en') {
            return "You are helping a private tutor write a daily student progress report in English, to be sent to parents via WhatsApp.

Your task: write ONE paragraph of a new report, matching the writing style, sentence structure, diction, and tone of the teacher's original reports below as closely as possible. Pay attention to how the teacher starts the report, explains the meeting/materi, and closes the paragraph.

=== TEACHER'S ORIGINAL REPORTS (STYLE REFERENCE) ===
{$examples}=== END OF EXAMPLES ===

Now write a new report using the following data:
- Date: {$formattedDate}
- Subject / class: {$student->subject}
- Meeting number: {$meetingNo}
- Student name: {$student->name}
- Lesson material today: {$materi}
- Student behavior/observation today: {$behavior}

Output rules:
- Write ONLY the report paragraph itself, no titles, no explanations, no quotation marks.
- Follow the same opening format as the examples.
- Do not make up technical details outside the provided material and behavior.";
        }

        return "Kamu membantu seorang guru les privat menulis laporan progres harian murid, dalam Bahasa Indonesia, untuk dikirim ke orang tua lewat WhatsApp.

Tugasmu: tulis SATU paragraf laporan baru, dengan gaya penulisan, struktur kalimat, diksi, dan nada yang SEPERSIS MUNGKIN meniru contoh-contoh laporan asli guru ini di bawah. Perhatikan pola pembukaan kalimat, cara menyebut meeting/materi, dan cara menutup paragraf yang biasa dipakai guru ini.

=== CONTOH LAPORAN ASLI GURU INI (referensi gaya) ===
{$examples}=== SELESAI CONTOH ===

Sekarang tulis laporan baru dengan data berikut:
- Tanggal: {$formattedDate}
- Mata pelajaran / kelas: {$student->subject}
- Meeting ke: {$meetingNo}
- Nama murid: {$student->name}
- Materi hari ini: {$materi}
- Behavior/observasi guru terhadap murid: {$behavior}

Aturan output:
- Tulis HANYA paragraf laporannya saja, tanpa judul, tanpa penjelasan tambahan, tanpa tanda kutip.
- Ikuti format pembuka yang sama seperti contoh (biasanya diawali tanggal, lalu nama mata pelajaran & meeting ke berapa).
- Jangan mengarang detail teknis di luar informasi materi dan behavior yang diberikan.";
    }
}
