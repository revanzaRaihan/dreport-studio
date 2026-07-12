<?php

namespace App\Services\Supabase;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseStorage
{
    /**
     * Upload a file to Supabase Storage.
     * Fallbacks to local storage if credentials are not configured.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @return string Public URL of the uploaded image
     */
    public static function upload($file, $path): string
    {
        $url = env('SUPABASE_URL');
        $key = env('SUPABASE_ANON_KEY');
        $bucket = 'reports';

        // Check if credentials are set. If not, fallback directly.
        if (empty($url) || empty($key)) {
            Log::info('Supabase credentials missing. Falling back to local storage.');
            return self::uploadLocal($file, $path);
        }

        try {
            $url = rtrim($url, '/');
            // Supabase upload endpoint: POST /storage/v1/object/{bucket}/{path}
            $endpoint = "{$url}/storage/v1/object/{$bucket}/{$path}";

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$key}",
                'apiKey' => $key,
                'Content-Type' => $file->getMimeType(),
            ])->withBody(
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            )->post($endpoint);

            if (!$response->successful()) {
                Log::error('Supabase Storage upload failed: ' . $response->body());
                throw new \Exception('Supabase API response: ' . $response->status() . ' - ' . $response->body());
            }

            // Public access URL: GET /storage/v1/object/public/{bucket}/{path}
            return "{$url}/storage/v1/object/public/{$bucket}/{$path}";

        } catch (\Exception $e) {
            Log::error('Supabase Storage upload exception: ' . $e->getMessage() . '. Falling back to local storage.');
            return self::uploadLocal($file, $path);
        }
    }

    /**
     * Store the file locally on the public disk.
     */
    private static function uploadLocal($file, $path): string
    {
        // Store on 'public' disk
        $dir = dirname($path);
        $filename = basename($path);

        $storedPath = $file->storeAs($dir, $filename, 'public');

        return asset('storage/' . $storedPath);
    }
}
