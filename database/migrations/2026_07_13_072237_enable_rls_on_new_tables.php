<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection($this->getConnection())->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pending_reports ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE schedules ENABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE schedule_student ENABLE ROW LEVEL SECURITY;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection($this->getConnection())->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pending_reports DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE schedules DISABLE ROW LEVEL SECURITY;');
            DB::statement('ALTER TABLE schedule_student DISABLE ROW LEVEL SECURITY;');
        }
    }
};
