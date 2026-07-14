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

class ReportGenerationFlowTest extends TestCase
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
            'subject' => 'Laravel Developer',
            'meeting_count' => 0
        ]);

        $response->assertRedirect('/students');
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Renziro Updated',
            'subject' => 'Laravel Developer',
            'meeting_count' => 0
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
            'body' => '03/07/2026 Javascript Developer Meeting 9: Renziro belajar loop.',
            'language' => 'id'
        ]);

        $response->assertRedirect('/dataset');
        $this->assertDatabaseHas('dataset_entries', [
            'body' => '03/07/2026 Javascript Developer Meeting 9: Renziro belajar loop.',
            'language' => 'id'
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
     * Test Dataset Entry Batch Delete.
     */
    public function test_dataset_batch_delete(): void
    {
        $this->actingAs($this->user);

        // Create General entry
        $entry1 = DatasetEntry::create([
            'body' => 'Contoh general dataset body',
            'language' => 'id',
            'section_type' => 'overview'
        ]);

        // Create Rec entry
        $entry2 = \App\Models\RecommendationDataset::create([
            'body' => 'Contoh recommendation dataset body',
            'language' => 'id',
            'category' => 'coding_dasar'
        ]);

        // 1. Delete batch with empty IDs
        $response = $this->delete('/dataset/batch-delete', ['ids' => []]);
        $response->assertRedirect('/dataset');
        $response->assertSessionHas('error');

        // 2. Delete batch with valid IDs
        $response = $this->delete('/dataset/batch-delete', ['ids' => [$entry1->id, $entry2->id]]);
        $response->assertRedirect('/dataset');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('dataset_entries', ['id' => $entry1->id]);
        $this->assertDatabaseMissing('recommendation_datasets', ['id' => $entry2->id]);
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
            $mock->shouldReceive('classifyCategory')
                 ->andReturn('logika_terstruktur');

            $mock->shouldReceive('generate')
                 ->once()
                 ->andReturn(new \App\DataTransferObjects\ReportSections(
                     'Renziro hari ini belajar database migrations dengan sangat baik.',
                     'Anak menunjukkan fokus tinggi dan kemajuan pesat dalam memahami konsep database.',
                     'Untuk latihan di rumah, disarankan membuat relasi migration sederhana.',
                     'Orang tua diimbau mendampingi Renziro saat mencoba membuat relasi table.'
                 ));
        });

        $expectedText = "09/07/2026\n\n" .
                        "Javascript Developer Meeting 6. Renziro Lesson 6\n\n" .
                        "Renziro hari ini belajar database migrations dengan sangat baik.\n\n" .
                        "-\n\n" .
                        "Anak menunjukkan fokus tinggi dan kemajuan pesat dalam memahami konsep database.\n\n" .
                        "Untuk latihan di rumah, disarankan membuat relasi migration sederhana.\n\n" .
                        "Orang tua diimbau mendampingi Renziro saat mencoba membuat relasi table.";

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
            'text' => $expectedText,
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
            'content' => $expectedText
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('reports', [
            'student_id' => $student->id,
            'student_name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_number' => 6,
            'content' => $expectedText
        ]);

        // Confirm meeting count incremented
        $student->refresh();
        $this->assertEquals(6, $student->meeting_count);

        $report = Report::first();

        // 3. Soft Delete Report from History
        $response = $this->delete("/history/{$report->id}");
        $response->assertRedirect(route('history.student', $student->id));
        $this->assertSoftDeleted('reports', [
            'id' => $report->id
        ]);
    }

    /**
     * Test Pending Report CRUD lifecycle.
     */
    public function test_pending_report_crud_lifecycle(): void
    {
        $this->actingAs($this->user);

        $student = Student::create([
            'name' => 'Adit',
            'subject' => 'Fisika Dasar',
            'meeting_count' => 2
        ]);

        // 1. Create Pending Report
        $response = $this->post('/pending-reports', [
            'student_id' => $student->id,
            'meeting_number' => 3,
            'report_date' => '2026-07-16'
        ]);

        $response->assertRedirect('/pending-reports');
        $this->assertDatabaseHas('pending_reports', [
            'student_id' => $student->id,
            'meeting_number' => 3
        ]);

        $pending = \App\Models\PendingReport::first();
        $this->assertEquals('2026-07-16', $pending->report_date->format('Y-m-d'));

        // 2. Update Pending Report
        $response = $this->put("/pending-reports/{$pending->id}", [
            'student_id' => $student->id,
            'meeting_number' => 4,
            'report_date' => '2026-07-17'
        ]);

        $response->assertRedirect('/pending-reports');
        $this->assertDatabaseHas('pending_reports', [
            'id' => $pending->id,
            'meeting_number' => 4
        ]);
        
        $pending->refresh();
        $this->assertEquals('2026-07-17', $pending->report_date->format('Y-m-d'));

        // 3. Delete Pending Report
        $response = $this->delete("/pending-reports/{$pending->id}");
        $response->assertRedirect('/pending-reports');
        $this->assertSoftDeleted('pending_reports', [
            'id' => $pending->id
        ]);
    }

    /**
     * Test Pending Report Batch Delete.
     */
    public function test_pending_report_batch_delete(): void
    {
        $this->actingAs($this->user);

        $student = Student::create([
            'name' => 'Budi Batch',
            'subject' => 'Kimia',
            'meeting_count' => 0
        ]);

        $pending1 = \App\Models\PendingReport::create([
            'student_id' => $student->id,
            'meeting_number' => 1,
            'report_date' => '2026-07-16'
        ]);

        $pending2 = \App\Models\PendingReport::create([
            'student_id' => $student->id,
            'meeting_number' => 2,
            'report_date' => '2026-07-17'
        ]);

        // 1. Delete batch with empty IDs
        $response = $this->delete('/pending-reports/batch-delete', ['ids' => []]);
        $response->assertRedirect('/pending-reports');
        $response->assertSessionHas('error');

        // 2. Delete batch with valid IDs
        $response = $this->delete('/pending-reports/batch-delete', ['ids' => [$pending1->id, $pending2->id]]);
        $response->assertRedirect('/pending-reports');
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('pending_reports', ['id' => $pending1->id]);
        $this->assertSoftDeleted('pending_reports', ['id' => $pending2->id]);
    }

    /**
     * Test saving a report automatically deletes the associated pending report.
     */
    public function test_saving_report_deletes_associated_pending_report(): void
    {
        $this->actingAs($this->user);

        $student = Student::create([
            'name' => 'Adit',
            'subject' => 'Fisika Dasar',
            'meeting_count' => 2
        ]);

        $pending = \App\Models\PendingReport::create([
            'student_id' => $student->id,
            'meeting_number' => 3,
            'report_date' => '2026-07-16'
        ]);

        $response = $this->post('/reports', [
            'student_id' => $student->id,
            'meeting_number' => 3,
            'report_date' => '2026-07-16',
            'materi' => 'Kinematika',
            'behavior' => 'Sangat antusias',
            'content' => 'Laporan hasil belajar kinetika.',
            'pending_report_id' => $pending->id
        ]);

        $response->assertRedirect('/');

        // Assert report is saved
        $this->assertDatabaseHas('reports', [
            'student_id' => $student->id,
            'meeting_number' => 3,
            'content' => 'Laporan hasil belajar kinetika.'
        ]);

        // Assert pending report is deleted (soft deleted)
        $this->assertSoftDeleted('pending_reports', [
            'id' => $pending->id
        ]);

        // Assert student meeting count incremented
        $student->refresh();
        $this->assertEquals(3, $student->meeting_count);
    }

    /**
     * Test saving report with image file upload (falling back to local).
     */
    public function test_saving_report_with_image_upload(): void
    {
        $this->actingAs($this->user);

        // Setup student
        $student = Student::create([
            'name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_count' => 5
        ]);

        // Fake the public storage disk
        \Illuminate\Support\Facades\Storage::fake('public');

        $fakeImage = \Illuminate\Http\UploadedFile::fake()->create('lesson.jpg', 100, 'image/jpeg');

        $response = $this->post('/reports', [
            'student_id' => $student->id,
            'meeting_number' => 6,
            'report_date' => '2026-07-09',
            'materi' => 'Database migrations',
            'behavior' => 'Sangat memahami penjelasan dengan baik',
            'content' => 'Ini adalah konten hasil generate AI.',
            'image' => $fakeImage
        ]);

        $response->assertRedirect('/');

        // Get the saved report to check the image URL
        $report = Report::where('student_id', $student->id)->where('meeting_number', 6)->first();
        $this->assertNotNull($report->image_url);

        // Extract the path details
        $storedFilename = basename($report->image_url);
        
        // Assert file was stored on public disk under reports/{student_id}
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists("reports/{$student->id}/{$storedFilename}");
    }

    /**
     * Test updating a report and uploading/replacing its image.
     */
    public function test_updating_report_and_image_upload(): void
    {
        $this->actingAs($this->user);

        $student = Student::create([
            'name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_count' => 5
        ]);

        $report = Report::create([
            'student_id' => $student->id,
            'student_name' => 'Renziro',
            'subject' => 'Javascript Developer',
            'meeting_number' => 6,
            'report_date' => '2026-07-09',
            'materi' => 'Initial Lesson',
            'behavior' => 'Sangat bagus',
            'content' => 'Konten awal.'
        ]);

        // Fake storage
        \Illuminate\Support\Facades\Storage::fake('public');

        $fakeImage = \Illuminate\Http\UploadedFile::fake()->create('updated_lesson.jpg', 100, 'image/jpeg');

        // PUT request to update report
        $response = $this->put("/reports/{$report->id}", [
            'meeting_number' => 7,
            'report_date' => '2026-07-10',
            'materi' => 'Updated Lesson',
            'behavior' => 'Kurang konsentrasi',
            'content' => 'Konten terupdate.',
            'image' => $fakeImage
        ]);

        $response->assertRedirect(); // redirects back

        $report->refresh();
        $this->assertEquals(7, $report->meeting_number);
        $this->assertEquals('2026-07-10', $report->report_date->format('Y-m-d'));
        $this->assertEquals('Updated Lesson', $report->materi);
        $this->assertEquals('Kurang konsentrasi', $report->behavior);
        $this->assertEquals('Konten terupdate.', $report->content);
        $this->assertNotNull($report->image_url);

        $storedFilename = basename($report->image_url);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists("reports/{$student->id}/{$storedFilename}");
    }

    /**
     * Test Schedule CRUD operations.
     */
    public function test_schedule_crud_operations(): void
    {
        $this->actingAs($this->user);

        $student = Student::create([
            'name' => 'Azzam Sched',
            'subject' => 'Fisika',
            'meeting_count' => 0
        ]);

        // 1. Create Schedule
        $response = $this->post('/schedule', [
            'day_of_week' => 3, // Rabu
            'start_time' => '15:00',
            'end_time' => '16:30',
            'label' => 'Ruang Steve Jobs',
            'student_ids' => [$student->id]
        ]);

        $response->assertRedirect('/schedule');
        
        $schedule = \App\Models\Schedule::first();
        $this->assertNotNull($schedule);
        $this->assertEquals(3, $schedule->day_of_week);
        $this->assertEquals('15:00', substr($schedule->start_time, 0, 5));
        $this->assertEquals('16:30', substr($schedule->end_time, 0, 5));
        $this->assertEquals('Ruang Steve Jobs', $schedule->label);
        $this->assertCount(1, $schedule->students);

        // 2. Update Schedule
        $response = $this->put("/schedule/{$schedule->id}", [
            'day_of_week' => 4, // Kamis
            'start_time' => '16:00',
            'end_time' => '17:30',
            'label' => 'Ruang Elon Musk',
            'student_ids' => [$student->id]
        ]);

        $response->assertRedirect('/schedule');
        
        $schedule->refresh();
        $this->assertEquals(4, $schedule->day_of_week);
        $this->assertEquals('16:00', substr($schedule->start_time, 0, 5));
        $this->assertEquals('17:30', substr($schedule->end_time, 0, 5));
        $this->assertEquals('Ruang Elon Musk', $schedule->label);

        // 3. Delete Schedule
        $response = $this->delete("/schedule/{$schedule->id}");
        $response->assertRedirect('/schedule');
        $this->assertSoftDeleted('schedules', [
            'id' => $schedule->id
        ]);
    }

    /**
     * Test automatic weekly pending report calculation.
     */
    public function test_automatic_pending_report_generation(): void
    {
        $this->actingAs($this->user);

        // Set current test date to Sunday July 12, 2026
        \Carbon\Carbon::setTestNow('2026-07-12');

        $student = Student::create([
            'name' => 'Azzam Sched Auto',
            'subject' => 'Kimia',
            'meeting_count' => 2
        ]);

        // Create weekly schedule slot for Wednesday (3)
        $schedule = \App\Models\Schedule::create([
            'day_of_week' => 3,
            'start_time' => '14:00',
            'end_time' => '15:30',
            'label' => 'Lab Kimia'
        ]);

        $schedule->students()->attach($student->id);

        // Run sync - should do nothing because there's no first_meeting_date and no history
        \App\Services\Schedule\PendingReportService::sync();
        $this->assertEquals(0, \App\Models\PendingReport::where('student_id', $student->id)->count());

        // Create one past report manually to establish reference history (Wednesday July 1, 2026 - Meeting 2)
        Report::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'subject' => $student->subject,
            'meeting_number' => 2,
            'report_date' => '2026-07-01',
            'materi' => 'Introduction',
            'behavior' => 'Good',
            'content' => 'Laporan sesi 2'
        ]);

        // Clear cache and run sync again
        \App\Services\Schedule\PendingReportService::clearCache();
        \App\Services\Schedule\PendingReportService::sync();

        // The closest Wednesday after July 1 up to July 12 is Wednesday July 8, 2026.
        // It should generate a pending report for Wednesday July 8 (Meeting 3)
        $pending = \App\Models\PendingReport::where('student_id', $student->id)->first();
        $this->assertNotNull($pending);
        $this->assertEquals(3, $pending->meeting_number);
        $this->assertEquals('2026-07-08', \Carbon\Carbon::parse($pending->report_date)->format('Y-m-d'));

        // Clean up Carbon test time
        \Carbon\Carbon::setTestNow();
    }

    /**
     * Test automatic weekly pending report calculation using first_meeting_date.
     */
    public function test_automatic_pending_report_generation_with_first_meeting_date(): void
    {
        $this->actingAs($this->user);

        // Set current test date to Sunday July 12, 2026
        \Carbon\Carbon::setTestNow('2026-07-12');

        $student = Student::create([
            'name' => 'Azzam First Date',
            'subject' => 'Fisika',
            'meeting_count' => 0,
            'first_meeting_date' => '2026-07-01' // Wednesday
        ]);

        // Create weekly schedule slot for Wednesday (3)
        $schedule = \App\Models\Schedule::create([
            'day_of_week' => 3,
            'start_time' => '14:00',
            'end_time' => '15:30',
            'label' => 'Lab Fisika'
        ]);

        $schedule->students()->attach($student->id);

        // Run sync
        \App\Services\Schedule\PendingReportService::sync();

        // The calculation should start exactly from Wednesday July 1, 2026!
        // It will generate pending reports for July 1 (Meeting 1) and July 8 (Meeting 2).
        $pendingReports = \App\Models\PendingReport::where('student_id', $student->id)
            ->orderBy('meeting_number', 'asc')
            ->get();

        $this->assertCount(2, $pendingReports);
        $this->assertEquals(1, $pendingReports[0]->meeting_number);
        $this->assertEquals('2026-07-01', \Carbon\Carbon::parse($pendingReports[0]->report_date)->format('Y-m-d'));
        $this->assertEquals(2, $pendingReports[1]->meeting_number);
        $this->assertEquals('2026-07-08', \Carbon\Carbon::parse($pendingReports[1]->report_date)->format('Y-m-d'));

        // Clean up Carbon test time
        \Carbon\Carbon::setTestNow();
    }

    /**
     * Test Settings update.
     */
    public function test_settings_can_be_updated(): void
    {
        $this->actingAs($this->user);

        // Update provider, model, key, WA number and locale
        $response = $this->put('/settings', [
            'ai_provider' => 'groq',
            'ai_model' => 'llama3-8b-8192',
            'ai_api_key' => 'new-secret-api-key',
            'admin_wa_number' => '628123456789',
            'app_locale' => 'en'
        ]);

        $response->assertRedirect('/settings');

        $this->assertEquals('groq', AppSetting::getValue('ai_provider'));
        $this->assertEquals('llama3-8b-8192', AppSetting::getValue('ai_model'));
        $this->assertEquals('new-secret-api-key', AppSetting::getValue('ai_api_key'));
        $this->assertEquals('628123456789', AppSetting::getValue('admin_wa_number'));
        $this->assertEquals('en', AppSetting::getValue('app_locale'));
    }

    /**
     * Test adding dataset entries with a specified language.
     */
    public function test_dataset_can_be_stored_with_language(): void
    {
        $this->actingAs($this->user);

        // Store Indonesian dataset
        $responseId = $this->post('/dataset', [
            'body' => 'Laporan bahasa indonesia',
            'language' => 'id'
        ]);
        $responseId->assertRedirect('/dataset');
        $this->assertDatabaseHas('dataset_entries', [
            'body' => 'Laporan bahasa indonesia',
            'language' => 'id'
        ]);

        // Store English dataset
        $responseEn = $this->post('/dataset', [
            'body' => 'Report in english',
            'language' => 'en'
        ]);
        $responseEn->assertRedirect('/dataset');
        $this->assertDatabaseHas('dataset_entries', [
            'body' => 'Report in english',
            'language' => 'en'
        ]);
    }

    /**
     * Test report generation validation checks language datasets.
     */
    public function test_report_generation_requires_matching_language_dataset(): void
    {
        $this->actingAs($this->user);

        $student = Student::create([
            'name' => 'Azzam Lang Test',
            'subject' => 'Fisika',
            'meeting_count' => 0
        ]);

        // Scenario A: Requesting 'en' report without English dataset
        DatasetEntry::create([
            'body' => 'Contoh Indonesia saja',
            'language' => 'id'
        ]);

        $response = $this->postJson('/reports/generate', [
            'student_id' => $student->id,
            'report_date' => '2026-07-12',
            'meeting_number' => 1,
            'materi' => 'Newton Laws',
            'behavior' => 'good',
            'language' => 'en'
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Silakan tambah minimal 1 contoh di tab Dataset Gaya untuk Bahasa Inggris biar AI tahu gaya nulis kamu.'
        ]);
    }

    /**
     * Test structured dataset storage and refactored report generation.
     */
    public function test_structured_dataset_storage_and_refactored_generation(): void
    {
        $this->actingAs($this->user);

        // 1. Store dataset entry with section_type = teachers_note
        $response = $this->post('/dataset', [
            'body' => 'Gaya catatan guru yang unik.',
            'language' => 'id',
            'section_type' => 'teachers_note'
        ]);
        $response->assertRedirect('/dataset');
        $this->assertDatabaseHas('dataset_entries', [
            'body' => 'Gaya catatan guru yang unik.',
            'language' => 'id',
            'section_type' => 'teachers_note'
        ]);

        // 2. Store recommendation dataset entry
        $responseRec = $this->post('/dataset', [
            'body' => 'Latihan kreativitas di rumah.',
            'language' => 'id',
            'section_type' => 'training_recommendation',
            'category' => 'kreativitas'
        ]);
        $responseRec->assertRedirect('/dataset');
        $this->assertDatabaseHas('recommendation_datasets', [
            'body' => 'Latihan kreativitas di rumah.',
            'language' => 'id',
            'category' => 'kreativitas'
        ]);
    }
}
