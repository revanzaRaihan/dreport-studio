<?php

namespace App\Actions\Report;

use App\Models\Student;
use App\Services\Ai\AiReportGeneratorInterface;
use App\DataTransferObjects\ReportSections;
use App\Services\Report\ReportAssembler;
use Exception;

class GenerateReportAction
{
    protected AiReportGeneratorInterface $generator;
    protected ClassifyStudentBehavior $classifyBehavior;
    protected BuildAiPrompt $buildPrompt;
    protected ReportAssembler $assembler;

    public function __construct(
        AiReportGeneratorInterface $generator,
        ClassifyStudentBehavior $classifyBehavior,
        BuildAiPrompt $buildPrompt,
        ReportAssembler $assembler
    ) {
        $this->generator = $generator;
        $this->classifyBehavior = $classifyBehavior;
        $this->buildPrompt = $buildPrompt;
        $this->assembler = $assembler;
    }

    /**
     * Orchestrate the full report generation process.
     *
     * @return array Contains 'text' (assembled report string) and 'warning' (nullable string)
     */
    public function execute(
        Student $student,
        int $meetingNumber,
        string $date,
        string $materi,
        string $behavior,
        string $language = 'id',
        string $reportType = 'full'
    ): array {
        // Step 1: Classify behavior
        $classification = $this->classifyBehavior->execute($behavior, $materi);
        $category = $classification->category;

        // Step 2: Generate report sections with retries
        $maxAttempts = 2;
        $attempt = 0;
        $reportSections = null;
        $error = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $prompt = $this->buildPrompt->execute(
                    $student,
                    $meetingNumber,
                    $date,
                    $materi,
                    $behavior,
                    $category,
                    $language
                );

                $reportSections = $this->generator->generate($prompt);

                // Run security and formatting validation
                $this->validateSections($reportSections);

                if ($this->isValidReport($reportSections)) {
                    break;
                }
                $error = "Hasil generate terindikasi terlalu pendek atau tidak lengkap pada percobaan ke-{$attempt}.";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if (!$reportSections) {
            throw new Exception('Gagal men-generate laporan terstruktur: ' . ($error ?? 'Alasan tidak diketahui'));
        }

        // Step 3: Assemble sections deterministically
        $outputText = $this->assembler->assemble($student, $meetingNumber, $date, $reportSections, $language, $reportType);

        // Step 4: Warning flag if it still doesn't satisfy safety length validation
        $warning = null;
        if (!$this->isValidReport($reportSections)) {
            $warning = 'Hasil generate laporan terindikasi terlalu pendek. Silakan periksa kembali hasil generate.';
        }

        return [
            'text' => $outputText,
            'warning' => $warning,
        ];
    }

    /**
     * Security and length validation for generated AI sections.
     */
    private function validateSections(ReportSections $sections): void
    {
        $fields = [
            'overview' => $sections->overview,
            'teachersNote' => $sections->teachersNote,
            'trainingRecommendation' => $sections->trainingRecommendation,
            'parentNote' => $sections->parentNote,
            'lessonCompleted' => $sections->lessonCompleted,
        ];

        foreach ($fields as $field => $value) {
            if (!is_string($value)) {
                throw new Exception("AI output field '{$field}' must be a string.");
            }

            $len = strlen($value);
            $minLen = ($field === 'lessonCompleted') ? 3 : 5;
            if ($len < $minLen || $len > 3000) {
                throw new Exception("AI output field '{$field}' has invalid length: {$len} characters (expected {$minLen}-3000).");
            }

            if (strip_tags($value) !== $value) {
                throw new Exception("AI output field '{$field}' contains forbidden HTML or script tags.");
            }
        }
    }

    /**
     * Helper to validate report sections output length.
     */
    private function isValidReport(ReportSections $sections): bool
    {
        if (empty($sections->overview) || empty($sections->teachersNote) || empty($sections->trainingRecommendation) || empty($sections->parentNote) || empty($sections->lessonCompleted)) {
            return false;
        }

        if (strlen($sections->overview) < 30) return false;
        if (strlen($sections->teachersNote) < 15) return false;
        if (strlen($sections->trainingRecommendation) < 15) return false;
        if (strlen($sections->parentNote) < 15) return false;
        if (strlen($sections->lessonCompleted) < 3) return false;

        return true;
    }
}
