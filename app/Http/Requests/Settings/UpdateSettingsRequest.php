<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ai_provider' => ['required', 'string', 'in:gemini,groq'],
            'ai_model' => ['required', 'string', 'max:255'],
            'ai_api_key' => ['nullable', 'string', 'max:500'],
            'admin_wa_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
