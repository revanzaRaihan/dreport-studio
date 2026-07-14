<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Display history for a specific student.
     */
    public function show(Request $request, Student $student): View
    {
        $search  = $request->query('search');
        $reports = Report::where('student_id', $student->id)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('materi', 'ilike', "%{$search}%")
                  ->orWhere('behavior', 'ilike', "%{$search}%")
                  ->orWhere('content', 'ilike', "%{$search}%");
            }))
            ->orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(1000)
            ->withQueryString();

        $adminWaNumber = \App\Models\AppSetting::getValue('admin_wa_number', '');

        return view('history.show', compact('student', 'reports', 'search', 'adminWaNumber'));
    }

    /**
     * Display a listing of saved reports, optionally filtered by student.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $studentsWithReports = Student::whereHas('reports')
            ->orderBy('name')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'ilike', "%{$search}%")
                       ->orWhere('subject', 'ilike', "%{$search}%");
                });
            })
            ->withCount('reports')
            ->paginate(1000)
            ->withQueryString();

        return view('history.index', compact('studentsWithReports', 'search'));
    }

    /**
     * Remove the specified report from storage (Soft Delete).
     */
    public function destroy(Report $report): RedirectResponse
    {
        $studentId = $report->student_id;
        $student = $report->student;
        $report->delete();

        if ($student && $student->meeting_count > 0) {
            $student->decrement('meeting_count');
        }

        $redirect = $studentId
            ? route('history.student', $studentId)
            : route('history.index');

        return redirect($redirect)->with('success', 'Riwayat laporan berhasil dihapus.');
    }
}
