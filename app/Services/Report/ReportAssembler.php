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
    public function assemble(Student $student, int $meetingNumber, string $date, ReportSections $sections): string
    {
        $formattedDate = Carbon::parse($date)->format('d/m/Y');
        
        $line1 = $formattedDate;
        $line2 = "{$student->subject} Meeting {$meetingNumber}. {$student->name} Lesson {$meetingNumber}";
        
        $overview = trim($sections->overview);
        $separator = "-";
        $teachersNote = trim($sections->teachersNote);
        $trainingRec = trim($sections->trainingRecommendation);
        $parentNote = trim($sections->parentNote);

        return implode("\n\n", [
            $line1,
            $line2,
            $overview,
            $separator,
            $teachersNote,
            $trainingRec,
            $parentNote
        ]);
    }
}
