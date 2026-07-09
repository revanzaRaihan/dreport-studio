<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display settings page.
     */
    public function index(): View
    {
        $provider = AppSetting::getValue('ai_provider', 'gemini');
        $model = AppSetting::getValue('ai_model', 'gemini-2.5-flash');
        $apiKey = AppSetting::getValue('ai_api_key', '');

        // Mask the API key for security in UI
        $maskedKey = '';
        if ($apiKey) {
            $length = strlen($apiKey);
            if ($length > 8) {
                $maskedKey = substr($apiKey, 0, 4) . str_repeat('*', $length - 8) . substr($apiKey, -4);
            } else {
                $maskedKey = str_repeat('*', $length);
            }
        }

        return view('settings.index', compact('provider', 'model', 'maskedKey'));
    }

    /**
     * Update settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AppSetting::setValue('ai_provider', $validated['ai_provider']);
        AppSetting::setValue('ai_model', $validated['ai_model']);

        $submittedKey = $validated['ai_api_key'];
        
        // Only update if it's not empty and doesn't contain asterisks (which means it's the masked value)
        if ($submittedKey !== null && strpos($submittedKey, '*') === false) {
            AppSetting::setValue('ai_api_key', $submittedKey);
        } elseif ($submittedKey === null || trim($submittedKey) === '') {
            // If they submit empty string, we can clear the API key
            AppSetting::setValue('ai_api_key', '');
        }

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }
}
