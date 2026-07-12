<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Report;
use App\Models\DatasetEntry;
use App\Services\Ai\AiReportGeneratorInterface;
use App\Actions\Report\BuildAiPrompt;
use App\Http\Requests\Report\GenerateReportRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
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

        $students = Student::orderBy('name')->get();
        
        // Calculate dynamic meeting numbers mapped by student ID
        $meetingNumbers = $students->mapWithKeys(fn($s) => [$s->id => $s->meeting_count + 1]);
        
        // Get dataset count for warnings if empty
        $datasetCount = DatasetEntry::count();

        // Get pending reports grouped by student ID
        $pendingReports = \App\Models\PendingReport::orderBy('meeting_number', 'asc')
            ->get()
            ->groupBy('student_id')
            ->map(function ($reports) {
                return $reports->map(fn($r) => [
                    'id' => $r->id,
                    'meeting_number' => $r->meeting_number,
                    'report_date' => $r->report_date->format('Y-m-d')
                ]);
            });

        return view('report.generate', compact('students', 'meetingNumbers', 'datasetCount', 'pendingReports'));
    }

    /**
     * Generate report via AI service.
     */
    public function generate(GenerateReportRequest $request, BuildAiPrompt $buildPrompt): JsonResponse
    {
        // Allow enough time for AI API to respond (Gemini 2.5-flash can be slow on reasoning)
        set_time_limit(120);

        $validated = $request->validated();
        $language = $validated['language'] ?? 'id';
        
        // Verify we have at least one dataset entry for the selected language
        if (DatasetEntry::where('language', $language)->count() === 0) {
            $langName = $language === 'en' ? 'Bahasa Inggris' : 'Bahasa Indonesia';
            return response()->json([
                'success' => false,
                'message' => "Silakan tambah minimal 1 contoh di tab Dataset Gaya untuk {$langName} biar AI tahu gaya nulis kamu."
            ], 422);
        }

        try {
            $student = Student::findOrFail($validated['student_id']);
            
            // Build prompt
            $prompt = $buildPrompt->execute(
                $student,
                $validated['meeting_number'],
                $validated['report_date'],
                $validated['materi'],
                $validated['behavior'],
                $language
            );

            // Call AI
            $outputText = $this->generator->generate($prompt);

            return response()->json([
                'success' => true,
                'text' => $outputText,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'subject' => $student->subject,
                'meeting_number' => $validated['meeting_number'],
                'report_date' => $validated['report_date'],
                'materi' => $validated['materi'],
                'behavior' => $validated['behavior'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save finalized report and increment student counter.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'meeting_number' => ['required', 'integer'],
            'report_date' => ['required', 'date'],
            'materi' => ['required', 'string'],
            'behavior' => ['required', 'string'],
            'content' => ['required', 'string'],
            'pending_report_id' => ['nullable', 'uuid', 'exists:pending_reports,id'],
            'image' => ['nullable', 'image', 'max:5120'], // Max 5MB
        ]);

        $student = Student::findOrFail($validated['student_id']);

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
        ]);

        // Delete the pending report if it was resolved
        if (!empty($validated['pending_report_id'])) {
            \App\Models\PendingReport::where('id', $validated['pending_report_id'])->delete();
        }

        // Increment student meeting count
        $student->increment('meeting_count');

        return redirect()->route('report.index')
            ->with('success', 'Laporan berhasil disimpan ke riwayat.');
    }

    /**
     * Update the specified report in storage.
     */
    public function update(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'meeting_number' => ['required', 'integer'],
            'report_date' => ['required', 'date'],
            'materi' => ['required', 'string'],
            'behavior' => ['required', 'string'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'], // Max 5MB
        ]);

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
