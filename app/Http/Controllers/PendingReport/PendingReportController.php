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

        $studentsWithPending = Student::whereHas('pendingReports')
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
            ->paginate(10)
            ->withQueryString();

        // Get all students for the dropdown select in the form
        $students = Student::orderBy('name')->get();

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
        $pendingReport->update($request->validated());

        return redirect()->route('pending-reports.index')
            ->with('success', 'Listing daily report berhasil diperbarui.');
    }

    /**
     * Remove the specified pending report from storage.
     */
    public function destroy(PendingReport $pendingReport): RedirectResponse
    {
        $pendingReport->delete();

        return redirect()->route('pending-reports.index')
            ->with('success', 'Listing daily report berhasil dihapus.');
    }
}
