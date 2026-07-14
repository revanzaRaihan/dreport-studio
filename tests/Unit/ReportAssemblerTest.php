<?php

namespace Tests\Unit;

use App\Models\Student;
use App\DataTransferObjects\ReportSections;
use App\Services\Report\ReportAssembler;
use Tests\TestCase;

class ReportAssemblerTest extends TestCase
{
    /**
     * Test report assembler correctly formats and joins report sections.
     */
    public function test_assembler_formats_correctly(): void
    {
        $student = new Student([
            'name' => 'Adit',
            'subject' => 'Scratch Junior',
        ]);

        $sections = new ReportSections(
            'Adit belajar membuat game balap hari ini.',
            'Anak sangat antusias dan mandiri.',
            'Coba modifikasi rintangan di rumah.',
            'Dampingi saat eksplorasi fitur baru.'
        );

        $assembler = new ReportAssembler();
        $date = '2026-07-14';

        $output = $assembler->assemble($student, 3, $date, $sections);

        $expected = "14/07/2026\n\n" .
                    "Scratch Junior Meeting 3. Adit Lesson 3\n\n" .
                    "Adit belajar membuat game balap hari ini.\n\n" .
                    "-\n\n" .
                    "Anak sangat antusias dan mandiri.\n\n" .
                    "Coba modifikasi rintangan di rumah.\n\n" .
                    "Dampingi saat eksplorasi fitur baru.";

        $this->assertEquals($expected, $output);
    }
}
