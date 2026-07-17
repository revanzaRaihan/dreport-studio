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
            '1. Game Balap Scratch: https://scratch.mit.edu',
            'Dampingi saat eksplorasi fitur baru.',
            'Lesson 3'
        );

        $assembler = new ReportAssembler();
        $date = '2026-07-14';

        $output = $assembler->assemble($student, 3, $date, $sections);

        $expected = "14/07/2026\n" .
                    "Scratch Junior Meeting 3, Pada pertemuan kali ini, Adit dapat menyelesaikan Lesson 3\n" .
                    "Adit belajar membuat game balap hari ini.\n" .
                    "-\n" .
                    "Anak sangat antusias dan mandiri.\n\n" .
                    "Training Rec:\n1. Game Balap Scratch: https://scratch.mit.edu\n\n" .
                    "Dampingi saat eksplorasi fitur baru. Adit tidak perlu menyelesaikan latihan hingga akhir.";

        $this->assertEquals($expected, $output);
    }

    /**
     * Test report assembler strips duplicate course/meeting headers from overview.
     */
    public function test_assembler_strips_duplicate_headers(): void
    {
        $student = new Student([
            'name' => 'Adit',
            'subject' => 'Scratch Junior',
        ]);

        $sections = new ReportSections(
            'Scratch Junior Meeting 3, Adit belajar membuat game balap hari ini.',
            'Anak sangat antusias dan mandiri.',
            '1. Game Balap Scratch: https://scratch.mit.edu',
            'Dampingi saat eksplorasi fitur baru.',
            'Lesson 3'
        );

        $assembler = new ReportAssembler();
        $date = '2026-07-14';

        $output = $assembler->assemble($student, 3, $date, $sections);

        $expected = "14/07/2026\n" .
                    "Scratch Junior Meeting 3, Pada pertemuan kali ini, Adit dapat menyelesaikan Lesson 3\n" .
                    "Adit belajar membuat game balap hari ini.\n" .
                    "-\n" .
                    "Anak sangat antusias dan mandiri.\n\n" .
                    "Training Rec:\n1. Game Balap Scratch: https://scratch.mit.edu\n\n" .
                    "Dampingi saat eksplorasi fitur baru. Adit tidak perlu menyelesaikan latihan hingga akhir.";

        $this->assertEquals($expected, $output);
    }
}
