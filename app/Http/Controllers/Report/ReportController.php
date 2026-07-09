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
        $students = Student::orderBy('name')->get();
        
        // Calculate dynamic meeting numbers mapped by student ID
        $meetingNumbers = $students->mapWithKeys(fn($s) => [$s->id => $s->meeting_count + 1]);
        
        // Get dataset count for warnings if empty
        $datasetCount = DatasetEntry::count();

        return view('report.generate', compact('students', 'meetingNumbers', 'datasetCount'));
    }

    /**
     * Generate report via AI service.
     */
    public function generate(GenerateReportRequest $request, BuildAiPrompt $buildPrompt): JsonResponse
    {
        $validated = $request->validated();
        
        // Verify we have at least one dataset entry
        if (DatasetEntry::count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan tambah minimal 1 contoh di tab Dataset Gaya biar AI tahu gaya nulis kamu.'
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
                $validated['behavior']
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
        ]);

        $student = Student::findOrFail($validated['student_id']);

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
        ]);

        // Increment student meeting count
        $student->increment('meeting_count');

        return redirect()->route('report.index')
            ->with('success', 'Laporan berhasil disimpan ke riwayat.');
    }
}
