<?php

namespace App\Services\Schedule;

use App\Models\Student;
use App\Models\Report;
use App\Models\PendingReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PendingReportService
{
    private static string $cacheKey = 'pending_reports_sync_timestamp';

    /**
     * Clear the sync status cache, forcing a refresh on next page load.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::$cacheKey);
    }

    /**
     * Synchronize and auto-calculate pending reports for all students.
     * Optimizes performance by pre-loading collections into memory to avoid N+1 query loops.
     */
    public static function sync(): void
    {
        // Performance optimization: skip sync if it has run recently
        if (Cache::has(self::$cacheKey)) {
            return;
        }

        try {
            // Get all students with their active schedules
            $students = Student::with('schedules')->get();

            if ($students->isEmpty()) {
                Cache::put(self::$cacheKey, true, 600); // cache for 10 mins
                return;
            }

            $studentIds = $students->pluck('id');

            // Pre-fetch only necessary columns (excluding heavy content text columns)
            $allReports = Report::select('id', 'student_id', 'report_date', 'meeting_number')
                ->whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id');

            // Pre-fetch only necessary columns for pending reports
            $allPendingReports = PendingReport::select('id', 'student_id', 'report_date')
                ->whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                if ($student->schedules->isEmpty()) {
                    continue;
                }

                // Get student's reports and pending reports collections
                $studentReports = $allReports->get($student->id, collect());
                $studentPending = $allPendingReports->get($student->id, collect());

                // Key reports by formatted date string for O(1) in-memory lookup
                $reportsByDate = $studentReports->keyBy(function ($r) {
                    return Carbon::parse($r->report_date)->format('Y-m-d');
                });

                $pendingByDate = $studentPending->keyBy(function ($p) {
                    return Carbon::parse($p->report_date)->format('Y-m-d');
                });

                foreach ($student->schedules as $schedule) {
                    $scheduleDay = (int) $schedule->day_of_week;

                    // Get the student's last saved report in memory
                    $lastReport = $studentReports->sortByDesc('report_date')->first();

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

                        // Check in-memory lists
                        $reportExists = $reportsByDate->has($dateStr);
                        $pendingExists = $pendingByDate->has($dateStr);

                        if (!$reportExists && !$pendingExists) {
                            $newPending = PendingReport::create([
                                'student_id' => $student->id,
                                'meeting_number' => $nextMeetingNumber,
                                'report_date' => $dateStr,
                            ]);

                            // Cache the newly created pending report to prevent double creation on other schedules of same student
                            $pendingByDate->put($dateStr, $newPending);
                        }

                        // Advance by 7 days
                        $nextDate->addWeek();
                        $nextMeetingNumber++;
                    }
                }
            }

            // Cache the sync timestamp for 10 minutes (600 seconds)
            Cache::put(self::$cacheKey, true, 600);

        } catch (\Exception $e) {
            Log::error('Auto pending report generation error: ' . $e->getMessage());
        }
    }
}
