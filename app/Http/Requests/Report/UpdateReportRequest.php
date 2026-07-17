<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meeting_number' => ['required', 'integer', 'min:1'],
            'report_date' => ['required', 'date'],
            'materi' => ['required', 'string', 'max:1000'],
            'behavior' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'], // Max 5MB
        ];
    }
}
