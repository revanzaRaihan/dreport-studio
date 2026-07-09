<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\DatasetEntry;
use App\Models\Report;
use App\Models\AppSetting;
use App\Services\Ai\AiReportGeneratorInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportStudioTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed default admin user
        $this->user = User::factory()->create([
            'email' => 'admin@reportstudio.test',
            'password' => bcrypt('password')
        ]);
    }

    /**
     * Test that all routes are protected by auth middleware.
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $routes = [
            '/',
            '/students',
            '/dataset',
            '/history',
            '/settings'
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /**
     * Test successful login.
     */
    public function test_admin_can_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@reportstudio.test',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    /**
     * Test Student CRUD operations.
     */
    public function test_student_crud_lifecycle(): void
    {
        $this->actingAs($this->user);

        // 1. Create Student
        $response = $this->post('/students', [
            'name' => 'Renziro',
            'subject' => 'Javascript Developer'
        ]);

        $response->assertRedirect('/students');
        $this->assertDatabaseHas('students', [
            'name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_count' => 0
        ]);

        $student = Student::first();

        // 2. Update Student
        $response = $this->put("/students/{$student->id}", [
            'name' => 'Renziro Updated',
            'subject' => 'Laravel Developer'
        ]);

        $response->assertRedirect('/students');
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Renziro Updated',
            'subject' => 'Laravel Developer'
        ]);

        // 3. Soft Delete Student
        $response = $this->delete("/students/{$student->id}");
        $response->assertRedirect('/students');
        
        $this->assertSoftDeleted('students', [
            'id' => $student->id
        ]);
    }

    /**
     * Test Dataset Entry CRUD.
     */
    public function test_dataset_entry_crud(): void
    {
        $this->actingAs($this->user);

        // 1. Create Entry
        $response = $this->post('/dataset', [
            'body' => '03/07/2026 Javascript Developer Meeting 9: Renziro belajar loop.'
        ]);

        $response->assertRedirect('/dataset');
        $this->assertDatabaseHas('dataset_entries', [
            'body' => '03/07/2026 Javascript Developer Meeting 9: Renziro belajar loop.'
        ]);

        $entry = DatasetEntry::first();

        // 2. Delete Entry
        $response = $this->delete("/dataset/{$entry->id}");
        $response->assertRedirect('/dataset');
        $this->assertDatabaseMissing('dataset_entries', [
            'id' => $entry->id
        ]);
    }

    /**
     * Test Report Generation and Storage.
     */
    public function test_report_generation_and_storage(): void
    {
        $this->actingAs($this->user);

        // Setup student and reference dataset
        $student = Student::create([
            'name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_count' => 5
        ]);

        DatasetEntry::create([
            'body' => 'Contoh laporan gaya penulisan les lama.'
        ]);

        // Mock the AI Report Generator Interface
        $this->mock(AiReportGeneratorInterface::class, function ($mock) {
            $mock->shouldReceive('generate')
                 ->once()
                 ->andReturn('Ini adalah konten hasil generate AI.');
        });

        // 1. Generate Report JSON endpoint
        $response = $this->postJson('/reports/generate', [
            'student_id' => $student->id,
            'report_date' => '2026-07-09',
            'meeting_number' => 6,
            'materi' => 'Database migrations',
            'behavior' => 'Sangat memahami penjelasan dengan baik'
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'text' => 'Ini adalah konten hasil generate AI.',
            'student_id' => $student->id,
            'meeting_number' => '6'
        ]);

        // 2. Save Report in Database
        $response = $this->post('/reports', [
            'student_id' => $student->id,
            'meeting_number' => 6,
            'report_date' => '2026-07-09',
            'materi' => 'Database migrations',
            'behavior' => 'Sangat memahami penjelasan dengan baik',
            'content' => 'Ini adalah konten hasil generate AI.'
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('reports', [
            'student_id' => $student->id,
            'student_name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_number' => 6,
            'content' => 'Ini adalah konten hasil generate AI.'
        ]);

        // Confirm meeting count incremented
        $student->refresh();
        $this->assertEquals(6, $student->meeting_count);

        $report = Report::first();

        // 3. Soft Delete Report from History
        $response = $this->delete("/history/{$report->id}");
        $response->assertRedirect('/history');
        $this->assertSoftDeleted('reports', [
            'id' => $report->id
        ]);
    }

    /**
     * Test Settings update.
     */
    public function test_settings_can_be_updated(): void
    {
        $this->actingAs($this->user);

        // Update provider, model and key
        $response = $this->put('/settings', [
            'ai_provider' => 'groq',
            'ai_model' => 'llama3-8b-8192',
            'ai_api_key' => 'new-secret-api-key'
        ]);

        $response->assertRedirect('/settings');

        $this->assertEquals('groq', AppSetting::getValue('ai_provider'));
        $this->assertEquals('llama3-8b-8192', AppSetting::getValue('ai_model'));
        $this->assertEquals('new-secret-api-key', AppSetting::getValue('ai_api_key'));
    }
}
