<?php

namespace App\Services\Schedule;

use App\Models\Student;
use App\Models\Report;
use App\Models\PendingReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PendingReportService
{
    /**
     * Synchronize and auto-calculate pending reports for all students.
     */
    public static function sync(): void
    {
        try {
            // Get all students with their active schedules
            $students = Student::with('schedules')->get();

            foreach ($students as $student) {
                if ($student->schedules->isEmpty()) {
                    continue;
                }

                foreach ($student->schedules as $schedule) {
                    $scheduleDay = (int) $schedule->day_of_week;

                    // Get the student's last saved report
                    $lastReport = Report::where('student_id', $student->id)
                        ->orderBy('report_date', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($lastReport) {
                        $lastDate = Carbon::parse($lastReport->report_date);
                        $lastMeeting = (int) $lastReport->meeting_number;
                        $lastDayIso = $lastDate->dayOfWeekIso;

                        // Calculate initial next date based on schedule day of week
                        if ($lastDayIso === $scheduleDay) {
                            $nextDate = $lastDate->copy()->addWeek();
                        } else {
                            $carbonDay = $scheduleDay === 7 ? 0 : $scheduleDay;
                            $nextDate = $lastDate->copy()->next($carbonDay);
                        }
                        $nextMeetingNumber = $lastMeeting + 1;
                    } else {
                        // If no past reports, use first_meeting_date if set, otherwise fallback to closest past scheduled day
                        if ($student->first_meeting_date) {
                            $nextDate = Carbon::parse($student->first_meeting_date);
                        } else {
                            $today = Carbon::today();
                            $todayIso = $today->dayOfWeekIso;

                            if ($todayIso === $scheduleDay) {
                                $nextDate = $today->copy();
                            } else {
                                $carbonDay = $scheduleDay === 7 ? 0 : $scheduleDay;
                                $nextDate = $today->copy()->previous($carbonDay);
                            }
                        }
                        $nextMeetingNumber = (int) $student->meeting_count + 1;
                    }

                    // Loop weekly to generate pending reports up to today
                    while ($nextDate->lte(Carbon::today())) {
                        $dateStr = $nextDate->format('Y-m-d');

                        // Check if report already exists for this date
                        $reportExists = Report::where('student_id', $student->id)
                            ->whereDate('report_date', $dateStr)
                            ->exists();

                        // Check if pending report already exists for this date
                        $pendingExists = PendingReport::where('student_id', $student->id)
                            ->whereDate('report_date', $dateStr)
                            ->exists();

                        if (!$reportExists && !$pendingExists) {
                            PendingReport::create([
                                'student_id' => $student->id,
                                'meeting_number' => $nextMeetingNumber,
                                'report_date' => $dateStr,
                            ]);
                        }

                        // Advance by 7 days
                        $nextDate->addWeek();
                        $nextMeetingNumber++;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Auto pending report generation error: ' . $e->getMessage());
        }
    }
}
