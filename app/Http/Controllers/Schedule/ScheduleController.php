<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Student;
use App\Services\Schedule\PendingReportService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the schedules.
     */
    public function index(): View
    {
        // Get all schedules grouped or ordered by day and start time
        $schedules = Schedule::with('students')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Get all students for multi-select dropdowns/checkboxes
        $students = Student::orderBy('name')->get();

        // Days mapping for display
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        // Distinct subjects for filtering
        $subjects = Student::whereNotNull('subject')
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
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:100'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
        ]);

        $schedule = Schedule::create([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'label' => $validated['label'],
        ]);

        $schedule->students()->sync($validated['student_ids'] ?? []);

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
        $validated = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'label' => ['nullable', 'string', 'max:100'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
        ]);

        $schedule->update([
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'label' => $validated['label'],
        ]);

        $schedule->students()->sync($validated['student_ids'] ?? []);

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
        // Detach students before soft-deleting
        $schedule->students()->detach();
        $schedule->delete();

        return redirect()->route('schedule.index')
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
