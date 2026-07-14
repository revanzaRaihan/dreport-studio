<?php

namespace App\Services\Ai;

use App\DataTransferObjects\ReportSections;

interface AiReportGeneratorInterface
{
    /**
     * Generate content based on a prompt.
     *
     * @param string $prompt
     * @return ReportSections
     */
    public function generate(string $prompt): ReportSections;

    /**
     * Classify behavior and materi into a recommendation category.
     *
     * @param string $behavior
     * @param string $materi
     * @return string
     */
    public function classifyCategory(string $behavior, string $materi): string;
}
