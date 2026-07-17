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
    private static function getCacheKey(): string
    {
        $userId = auth()->id() ?? 'global';
        return 'pending_reports_sync_timestamp_' . $userId;
    }

    /**
     * Clear the sync status cache, forcing a refresh on next page load.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::getCacheKey());
    }

    /**
     * Synchronize and auto-calculate pending reports for all students.
     * Optimizes performance by pre-loading collections into memory to avoid N+1 query loops.
     */
    public static function sync(): void
    {
        $cacheKey = self::getCacheKey();

        // Performance optimization: skip sync if it has run recently
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            // Get all students with their active schedules (scoped to user if authenticated)
            $studentsQuery = Student::with('schedules');
            if (auth()->check()) {
                $studentsQuery->where('user_id', auth()->id());
            }
            $students = $studentsQuery->get();

            if ($students->isEmpty()) {
                Cache::put($cacheKey, true, 600); // cache for 10 mins
            }

            $studentIds = $students->pluck('id');

            // Pre-fetch only necessary columns (excluding heavy content text columns)
            $allReports = Report::select('id', 'student_id', 'report_date', 'meeting_number')
                ->whereIn('student_id', $studentIds)
                ->orderBy('report_date', 'desc')
                ->orderBy('meeting_number', 'desc')
                ->get()
                ->groupBy('student_id');

            // Pre-fetch only necessary columns for pending reports
            $allPendingReports = PendingReport::select('id', 'student_id', 'report_date', 'meeting_number')
                ->whereIn('student_id', $studentIds)
                ->orderBy('report_date', 'desc')
                ->orderBy('meeting_number', 'desc')
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

                // Find the last saved report in history (ignore pending reports for reference)
                $lastReport = $studentReports->first();

                // Determine start date and meeting number
                if ($lastReport) {
                    $startDate = Carbon::parse($lastReport->report_date);
                    $startMeetingNumber = (int) $lastReport->meeting_number;
                } else {
                    // If no past reports, check first_meeting_date
                    if ($student->first_meeting_date) {
                        // Start from the day before first_meeting_date so that first_meeting_date itself is checked in the loop
                        $startDate = Carbon::parse($student->first_meeting_date)->subDay();
                        $startMeetingNumber = (int) $student->meeting_count;
                    } else {
                        // If no first_meeting_date AND no past reports, skip!
                        continue;
                    }
                }

                // Compile active schedule days for this student
                $scheduleDays = $student->schedules->pluck('day_of_week')->map(fn($d) => (int)$d)->toArray();

                // Loop day-by-day from $startDate + 1 day to Carbon::today()
                $nextDate = $startDate->copy()->addDay();
                $nextMeetingNumber = $startMeetingNumber + 1;

                while ($nextDate->lte(Carbon::today())) {
                    $dayOfWeekIso = $nextDate->dayOfWeekIso;

                    if (in_array($dayOfWeekIso, $scheduleDays)) {
                        $dateStr = $nextDate->format('Y-m-d');

                        $reportExists = $reportsByDate->has($dateStr);
                        $pendingExists = $pendingByDate->has($dateStr);

                        if (!$reportExists && !$pendingExists) {
                            $newPending = PendingReport::create([
                                'student_id' => $student->id,
                                'meeting_number' => $nextMeetingNumber,
                                'report_date' => $dateStr,
                            ]);

                            // Cache to prevent double creation on other code logic
                            $pendingByDate->put($dateStr, $newPending);
                        }

                        $nextMeetingNumber++;
                    }

                    $nextDate->addDay();
                }
            }

            // Cache the sync timestamp for 10 minutes (600 seconds)
            Cache::put($cacheKey, true, 600);

        } catch (\Exception $e) {
            Log::error('Auto pending report generation error: ' . $e->getMessage());
        }
    }
}
