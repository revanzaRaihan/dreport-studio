<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Report;
use App\Models\DatasetEntry;
use App\Services\Ai\AiReportGeneratorInterface;
use App\Actions\Report\BuildAiPrompt;
use App\Actions\Report\GenerateReportAction;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Requests\Report\UpdateReportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Exception;

class ReportController extends Controller
{
    protected AiReportGeneratorInterface $generator;

    public function __construct(AiReportGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Show report builder form.
     */
    public function index(): View
    {
        \App\Services\Schedule\PendingReportService::sync();

        $students = Student::where('user_id', auth()->id())->with('schedules')->orderBy('name')->get();
        
        // Calculate dynamic meeting numbers mapped by student ID
        $latestReports = Report::select('student_id', 'meeting_number', 'report_date')
            ->where('user_id', auth()->id())
            ->whereIn('student_id', $students->pluck('id'))
            ->orderBy('report_date', 'desc')
            ->orderBy('meeting_number', 'desc')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        $meetingNumbers = $students->mapWithKeys(function ($s) use ($latestReports) {
            $lastReport = $latestReports->get($s->id);
            $nextMeeting = $lastReport ? $lastReport->meeting_number + 1 : $s->meeting_count + 1;
            return [$s->id => $nextMeeting];
        });

        // Calculate next scheduled dates mapped by student ID
        $nextDates = $students->mapWithKeys(function ($s) use ($latestReports) {
            $lastReport = $latestReports->get($s->id);
            $nextDate = null;

            if ($lastReport) {
                $lastDate = \Carbon\Carbon::parse($lastReport->report_date);
                if ($s->schedules->isNotEmpty()) {
                    $scheduleDays = $s->schedules->pluck('day_of_week')->map(fn($d) => (int)$d)->toArray();
                    $checkDate = $lastDate->copy()->addDay();
                    for ($i = 0; $i < 14; $i++) {
                        if (in_array($checkDate->dayOfWeekIso, $scheduleDays)) {
                            $nextDate = $checkDate->format('Y-m-d');
                            break;
                        }
                        $checkDate->addDay();
                    }
                }
                if (!$nextDate) {
                    $nextDate = $lastDate->copy()->addWeek()->format('Y-m-d');
                }
            } else {
                if ($s->first_meeting_date) {
                    $nextDate = \Carbon\Carbon::parse($s->first_meeting_date)->format('Y-m-d');
                } else {
                    $nextDate = \Carbon\Carbon::today()->format('Y-m-d');
                }
            }

            return [$s->id => $nextDate];
        });
        
        // Get dataset count for warnings if empty (scoped to user)
        $datasetCount = DatasetEntry::where('user_id', auth()->id())->count();

        // Get pending reports grouped by student ID
        $pendingReports = \App\Models\PendingReport::whereHas('student', function ($q) {
                $q->where('user_id', auth()->id());
            })
            ->orderBy('meeting_number', 'asc')
            ->get()
            ->groupBy('student_id')
            ->map(function ($reports) {
                return $reports->map(fn($r) => [
                    'id' => $r->id,
                    'meeting_number' => $r->meeting_number,
                    'report_date' => $r->report_date->format('Y-m-d')
                ]);
            });

        return view('report.generate', compact('students', 'meetingNumbers', 'nextDates', 'datasetCount', 'pendingReports'));
    }

    /**
     * Generate report via AI service.
     */
    public function generate(
        GenerateReportRequest $request,
        GenerateReportAction $generateReportAction
    ): JsonResponse {
        // Allow enough time for AI API to respond
        set_time_limit(120);

        $validated = $request->validated();
        $language = $validated['language'] ?? 'id';
        
        // Verify we have at least one dataset entry for the selected language and user
        if (DatasetEntry::where('user_id', auth()->id())->where('language', $language)->count() === 0) {
            $langName = $language === 'en' ? 'Bahasa Inggris' : 'Bahasa Indonesia';
            return response()->json([
                'success' => false,
                'message' => "Silakan tambah minimal 1 contoh di tab Dataset Gaya untuk {$langName} biar AI tahu gaya nulis kamu."
            ], 422);
        }

        try {
            $student = Student::where('user_id', auth()->id())->findOrFail($validated['student_id']);
            
            // Execute the action
            $result = $generateReportAction->execute(
                $student,
                $validated['meeting_number'],
                $validated['report_date'],
                $validated['materi'],
                $validated['behavior'],
                $language,
                $validated['report_type'] ?? 'full'
            );

            return response()->json([
                'success' => true,
                'text' => $result['text'],
                'warning' => $result['warning'],
                'student_id' => $student->id,
                'student_name' => $student->name,
                'subject' => $student->subject,
                'meeting_number' => $validated['meeting_number'],
                'report_date' => $validated['report_date'],
                'materi' => $validated['materi'],
                'behavior' => $validated['behavior'],
            ]);
        } catch (Exception $e) {
            // Log full details on server, return generic error to frontend for security
            Log::error('AI Laporan Generation Exception: ' . $e->getMessage(), [
                'exception' => $e,
                'validated' => $validated
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Maaf, terjadi kesalahan sistem saat memproses laporan dengan AI. Silakan coba kembali.'
            ], 500);
        }
    }

    /**
     * Save finalized report and increment student counter.
     */
    public function store(StoreReportRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::where('user_id', auth()->id())->findOrFail($validated['student_id']);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $path = 'reports/' . $student->id . '/' . time() . '_' . uniqid() . '.' . $extension;

            $imageUrl = \App\Services\Supabase\SupabaseStorage::upload($file, $path);
        }

        // Save report
        Report::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'subject' => $student->subject,
            'meeting_number' => $validated['meeting_number'],
            'report_date' => $validated['report_date'],
            'materi' => $validated['materi'],
            'behavior' => $validated['behavior'],
            'content' => $validated['content'],
            'image_url' => $imageUrl,
            'user_id' => auth()->id(),
        ]);

        // Delete the pending report if it was resolved
        if (!empty($validated['pending_report_id'])) {
            \App\Models\PendingReport::where('id', $validated['pending_report_id'])
                ->whereHas('student', function ($q) {
                    $q->where('user_id', auth()->id());
                })
                ->delete();
        }

        // Increment student meeting count
        $student->increment('meeting_count');

        return redirect()->route('report.index')
            ->with('success', 'Laporan berhasil disimpan ke riwayat.');
    }

    /**
     * Update the specified report in storage.
     */
    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        $validated = $request->validated();

        // Enforce ownership
        if ($report->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $imageUrl = $report->image_url;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $path = 'reports/' . ($report->student_id ?? 'global') . '/' . time() . '_' . uniqid() . '.' . $extension;

            $imageUrl = \App\Services\Supabase\SupabaseStorage::upload($file, $path);
        }

        $report->update([
            'meeting_number' => $validated['meeting_number'],
            'report_date' => $validated['report_date'],
            'materi' => $validated['materi'],
            'behavior' => $validated['behavior'],
            'content' => $validated['content'],
            'image_url' => $imageUrl,
        ]);

        return redirect()->back()
            ->with('success', 'Laporan berhasil diperbarui.');
    }
}
