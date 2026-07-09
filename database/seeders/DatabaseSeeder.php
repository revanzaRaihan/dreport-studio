<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create single admin user
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@reportstudio.test')],
            [
                'name' => 'Report Studio Admin',
                'password' => bcrypt(env('ADMIN_PASSWORD', 'password')),
            ]
        );

        // Seed default app settings
        \DB::table('app_settings')->updateOrInsert(
            ['key' => 'ai_provider'],
            ['value' => env('AI_PROVIDER', 'gemini'), 'created_at' => now(), 'updated_at' => now()]
        );

        \DB::table('app_settings')->updateOrInsert(
            ['key' => 'ai_model'],
            ['value' => env('AI_MODEL', 'gemini-2.5-flash'), 'created_at' => now(), 'updated_at' => now()]
        );

        \DB::table('app_settings')->updateOrInsert(
            ['key' => 'ai_api_key'],
            ['value' => env('AI_API_KEY'), 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
