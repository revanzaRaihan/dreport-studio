<?php

namespace App\DataTransferObjects;

class ClassificationResult
{
    public function __construct(
        public string $category,
        public string $explanation = ''
    ) {}
}
