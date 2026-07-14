<?php

namespace Tests\Unit;

use App\Actions\Report\ClassifyStudentBehavior;
use App\Services\Ai\AiReportGeneratorInterface;
use Tests\TestCase;
use Mockery;

class ClassifyStudentBehaviorTest extends TestCase
{
    /**
     * Test successful classification returns category.
     */
    public function test_classify_student_behavior_success(): void
    {
        $mockGenerator = Mockery::mock(AiReportGeneratorInterface::class);
        $mockGenerator->shouldReceive('classifyCategory')
            ->once()
            ->with('anak aktif dan suka desain visual', 'CSS Grid')
            ->andReturn('kreativitas');

        $action = new ClassifyStudentBehavior($mockGenerator);
        $result = $action->execute('anak aktif dan suka desain visual', 'CSS Grid');

        $this->assertEquals('kreativitas', $result->category);
        $this->assertEquals('', $result->explanation);
    }

    /**
     * Test failed classification returns fallback category coding_dasar.
     */
    public function test_classify_student_behavior_fallback_on_failure(): void
    {
        $mockGenerator = Mockery::mock(AiReportGeneratorInterface::class);
        $mockGenerator->shouldReceive('classifyCategory')
            ->once()
            ->andThrow(new \Exception('API Error'));

        $action = new ClassifyStudentBehavior($mockGenerator);
        $result = $action->execute('error behavior', 'error materi');

        $this->assertEquals('coding_dasar', $result->category);
        $this->assertEquals('API Error', $result->explanation);
    }
}
