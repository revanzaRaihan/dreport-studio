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
        string $language = 'id'
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
        $outputText = $this->assembler->assemble($student, $meetingNumber, $date, $reportSections);

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
     * Helper to validate report sections output length.
     */
    private function isValidReport(ReportSections $sections): bool
    {
        if (empty($sections->overview) || empty($sections->teachersNote) || empty($sections->trainingRecommendation) || empty($sections->parentNote)) {
            return false;
        }

        if (strlen($sections->overview) < 50) return false;
        if (strlen($sections->teachersNote) < 40) return false;
        if (strlen($sections->trainingRecommendation) < 50) return false;
        if (strlen($sections->parentNote) < 40) return false;

        return true;
    }
}
