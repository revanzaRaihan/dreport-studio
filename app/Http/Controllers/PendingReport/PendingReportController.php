<?php

namespace App\Http\Controllers\PendingReport;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\PendingReport;
use App\Http\Requests\PendingReport\StorePendingReportRequest;
use App\Http\Requests\PendingReport\UpdatePendingReportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PendingReportController extends Controller
{
    /**
     * Display a listing of the pending reports.
     */
    public function index(Request $request): View
    {
        \App\Services\Schedule\PendingReportService::sync();

        $search = $request->query('search');

        $studentsWithPending = Student::where('user_id', auth()->id())
            ->whereHas('pendingReports')
            ->orderBy('name')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'ilike', "%{$search}%")
                       ->orWhere('subject', 'ilike', "%{$search}%");
                });
            })
            ->with(['pendingReports' => function ($q) {
                $q->orderBy('report_date', 'asc');
            }])
            ->paginate(1000)
            ->withQueryString();

        // Get all students for the dropdown select in the form (scoped to user)
        $students = Student::where('user_id', auth()->id())->orderBy('name')->get();

        return view('pending-reports.index', compact('studentsWithPending', 'students', 'search'));
    }

    /**
     * Store a newly created pending report in storage.
     */
    public function store(StorePendingReportRequest $request): RedirectResponse
    {
        PendingReport::create($request->validated());

        return redirect()->route('pending-reports.index')
            ->with('success', 'Listing daily report berhasil ditambahkan.');
    }

    /**
     * Update the specified pending report in storage.
     */
    public function update(UpdatePendingReportRequest $request, PendingReport $pendingReport): RedirectResponse
    {
        if ($pendingReport->student->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $pendingReport->update($request->validated());

        return redirect()->route('pending-reports.index')
            ->with('success', 'Listing daily report berhasil diperbarui.');
    }

    /**
     * Delete multiple pending reports in one batch.
     */
    public function batchDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        
        if (!empty($ids)) {
            $deleted = PendingReport::whereIn('id', $ids)
                ->whereHas('student', function ($q) {
                    $q->where('user_id', auth()->id());
                })
                ->delete();

            return redirect()->route('pending-reports.index')
                ->with('success', $deleted . ' antrean laporan berhasil dihapus.');
        }

        return redirect()->route('pending-reports.index')
            ->with('error', 'Tidak ada antrean laporan yang dipilih.');
    }

    /**
     * Remove the specified pending report from storage.
     */
    public function destroy(PendingReport $pendingReport): RedirectResponse
    {
        if ($pendingReport->student->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $pendingReport->delete();

        return redirect()->route('pending-reports.index')
            ->with('success', 'Listing daily report berhasil dihapus.');
    }
}
