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
        // Enforce student ownership
        if ($student->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $search  = $request->query('search');
        $reports = Report::where('student_id', $student->id)
            ->where('user_id', auth()->id())
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

        $studentsWithReports = Student::where('user_id', auth()->id())
            ->whereHas('reports')
            ->addSelect([
                'latest_report_date' => Report::select('report_date')
                    ->whereColumn('student_id', 'students.id')
                    ->where('user_id', auth()->id())
                    ->orderBy('report_date', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit(1)
            ])
            ->orderBy('latest_report_date', 'desc')
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
        // Enforce ownership
        if ($report->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $studentId = $report->student_id;
        $student = $report->student;
        
        $report->delete();

        if ($student && $student->user_id === auth()->id() && $student->meeting_count > 0) {
            $student->decrement('meeting_count');
        }

        $redirect = $studentId
            ? route('history.student', $studentId)
            : route('history.index');

        return redirect($redirect)->with('success', 'Riwayat laporan berhasil dihapus.');
    }
}
