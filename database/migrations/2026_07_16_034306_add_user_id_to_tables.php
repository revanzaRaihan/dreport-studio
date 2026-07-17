<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['students', 'reports', 'schedules', 'dataset_entries', 'recommendation_datasets'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                });
            }
        }

        // Assign existing data to the first user
        $firstUser = DB::table('users')->first();
        if ($firstUser) {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->whereNull('user_id')->update(['user_id' => $firstUser->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['students', 'reports', 'schedules', 'dataset_entries', 'recommendation_datasets'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $table) {
                    // Drop foreign key safely
                    try {
                        $table->dropForeign($table . '_user_id_foreign');
                    } catch (\Exception $e) {
                        // ignore if fallback fails
                    }
                    $table->dropColumn('user_id');
                });
            }
        }
    }
};
