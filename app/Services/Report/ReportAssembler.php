<?php

namespace App\Services\Report;

use App\Models\Student;
use App\DataTransferObjects\ReportSections;
use Carbon\Carbon;

class ReportAssembler
{
    /**
     * Assemble the report sections into a formatted string.
     */
    public function assemble(Student $student, int $meetingNumber, string $date, ReportSections $sections, string $language = 'id', string $reportType = 'full'): string
    {
        $formattedDate = Carbon::parse($date)->format('d/m/Y');
        
        $line1 = $formattedDate;
        
        $lessonText = trim($sections->lessonCompleted);
        if (!empty($lessonText)) {
            if ($language === 'en') {
                $line2 = "{$student->subject} Meeting {$meetingNumber}, In this meeting, {$student->name} completed {$lessonText}";
            } else {
                $line2 = "{$student->subject} Meeting {$meetingNumber}, Pada pertemuan kali ini, {$student->name} dapat menyelesaikan {$lessonText}";
            }
        } else {
            $line2 = "{$student->subject} Meeting {$meetingNumber}";
        }
        
        $overview = trim($sections->overview);
        
        // Remove course name/meeting/lesson header prefixes to prevent double headers
        $subjectEscaped = preg_quote($student->subject, '/');
        $overview = preg_replace('/^(' . $subjectEscaped . '|.+?)?\s*(Meeting|Lesson)\s*\d+[\s,.]*/i', '', $overview);
        
        $line3 = $overview;
        
        if ($reportType === 'overview') {
            return $line1 . "\n" .
                $line2 . "\n" .
                $line3;
        }
        
        $separator = "-";
        $teachersNote = trim($sections->teachersNote);
        $trainingRec = trim($sections->trainingRecommendation);
        $parentNote = trim($sections->parentNote);

        // Prepend Training Rec header if not present
        if (!empty($trainingRec)) {
            if (stripos($trainingRec, 'Training Rec:') === false) {
                $trainingRec = "Training Rec:\n" . $trainingRec;
            }
        }

        // Programmatically append the disclaimer if missing
        if ($language === 'en') {
            $mandatoryPhrase = "{$student->name} does not need to complete the exercises to the end.";
        } else {
            $mandatoryPhrase = "{$student->name} tidak perlu menyelesaikan latihan hingga akhir.";
        }

        if (stripos($parentNote, 'tidak perlu menyelesaikan latihan') === false && stripos($parentNote, 'does not need to complete') === false) {
            if (!empty($parentNote)) {
                if (!preg_match('/[.!?]$/', $parentNote)) {
                    $parentNote .= '.';
                }
                $parentNote .= ' ' . $mandatoryPhrase;
            } else {
                $parentNote = $mandatoryPhrase;
            }
        }

        return $line1 . "\n" .
            $line2 . "\n" .
            $line3 . "\n" .
            $separator . "\n" .
            $teachersNote . "\n\n" .
            $trainingRec . "\n\n" .
            $parentNote;
    }
}
