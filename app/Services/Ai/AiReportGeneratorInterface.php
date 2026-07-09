<?php

namespace App\Services\Ai;

interface AiReportGeneratorInterface
{
    /**
     * Generate content based on a prompt.
     *
     * @param string $prompt
     * @return string
     */
    public function generate(string $prompt): string;
}
