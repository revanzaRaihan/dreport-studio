<?php

namespace App\Actions\Report;

use App\Services\Ai\AiReportGeneratorInterface;
use App\DataTransferObjects\ClassificationResult;

class ClassifyStudentBehavior
{
    protected AiReportGeneratorInterface $generator;

    public function __construct(AiReportGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Classify the student's behavior and material into a recommendation category.
     */
    public function execute(string $behavior, string $materi): ClassificationResult
    {
        try {
            $category = $this->generator->classifyCategory($behavior, $materi);
            return new ClassificationResult($category);
        } catch (\Exception $e) {
            // Safe fallback to 'coding_dasar'
            return new ClassificationResult('coding_dasar', $e->getMessage());
        }
    }
}
