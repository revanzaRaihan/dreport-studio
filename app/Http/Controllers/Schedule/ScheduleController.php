<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\Schedule\PendingReportService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the schedules.
     */
    public function index(): View
    {
        // Get all schedules for the logged-in user
        $schedules = Schedule::where('user_id', auth()->id())
            ->with(['students' => function ($q) {
                $q->where('user_id', auth()->id());
            }])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Get all students for multi-select dropdowns/checkboxes (scoped to user)
        $students = Student::where('user_id', auth()->id())->orderBy('name')->get();

        // Days mapping for display
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        // Distinct subjects for filtering (scoped to user)
        $subjects = Student::where('user_id', auth()->id())
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');

        return view('schedule.index', compact('schedules', 'students', 'days', 'subjects'));
    }

    /**
     * Store a newly created schedule in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:100'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [
                'uuid',
                Rule::exists('students', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())->whereNull('deleted_at');
                })
            ],
        ]);

        $schedule = Schedule::create([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'label' => $validated['label'],
            'user_id' => auth()->id(),
        ]);

        $schedule->students()->sync($validated['student_ids'] ?? []);

        // Clear sync cache to force recalculation
        PendingReportService::clearCache();

        // Sync pending reports immediately to reflect the new schedule
        PendingReportService::sync();

        return redirect()->route('schedule.index')
            ->with('success', 'Jadwal berhasil ditambahkan.');
    }

    /**
     * Update the specified schedule in storage.
     */
    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        // Enforce ownership
        if ($schedule->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:100'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [
                'uuid',
                Rule::exists('students', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())->whereNull('deleted_at');
                })
            ],
        ]);

        $schedule->update([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'label' => $validated['label'],
        ]);

        $schedule->students()->sync($validated['student_ids'] ?? []);

        // Clear sync cache to force recalculation
        PendingReportService::clearCache();

        // Sync pending reports immediately to reflect schedule changes
        PendingReportService::sync();

        return redirect()->route('schedule.index')
            ->with('success', 'Jadwal berhasil diperbarui.');
    }

    /**
     * Remove the specified schedule from storage.
     */
    public function destroy(Schedule $schedule): RedirectResponse
    {
        // Enforce ownership
        if ($schedule->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Detach students before soft-deleting
        $schedule->students()->detach();
        $schedule->delete();

        PendingReportService::clearCache();

        return redirect()->route('schedule.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
