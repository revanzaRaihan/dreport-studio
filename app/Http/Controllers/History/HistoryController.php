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
            ->paginate(5)
            ->withQueryString();

        return view('history.show', compact('student', 'reports', 'search'));
    }

    /**
     * Display a listing of saved reports, optionally filtered by student.
     */
    public function index(Request $request): View
    {
        $studentId = $request->query('student_id');
        $search    = $request->query('search');
        $student   = $studentId ? Student::withTrashed()->find($studentId) : null;

        $reports = Report::orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->when($student, fn($q) => $q->where('student_id', $student->id))
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('student_name', 'ilike', "%{$search}%")
                  ->orWhere('materi', 'ilike', "%{$search}%")
                  ->orWhere('behavior', 'ilike', "%{$search}%");
            }))
            ->paginate(5)
            ->withQueryString();

        $students = Student::orderBy('name')->get();

        return view('history.index', compact('reports', 'student', 'students', 'search'));
    }

    /**
     * Remove the specified report from storage (Soft Delete).
     */
    public function destroy(Report $report): RedirectResponse
    {
        $studentId = $report->student_id;
        $report->delete();

        $redirect = $studentId
            ? route('history.student', $studentId)
            : route('history.index');

        return redirect($redirect)->with('success', 'Riwayat laporan berhasil dihapus.');
    }
}
