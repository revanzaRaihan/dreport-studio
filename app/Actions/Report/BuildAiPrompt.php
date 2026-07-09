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
    public function execute(Student $student, string $meetingNo, string $dateVal, string $materi, string $behavior): string
    {
        // Load the last 12 dataset entries for few-shot examples
        $entries = DatasetEntry::latest()->take(12)->get()->reverse();

        $examples = '';
        $index = 1;
        foreach ($entries as $entry) {
            $examples .= "Contoh {$index}:\n" . trim($entry->body) . "\n\n";
            $index++;
        }

        // Format date to DD/MM/YYYY
        $formattedDate = Carbon::parse($dateVal)->format('d/m/Y');

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
