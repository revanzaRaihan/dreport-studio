<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'uuid', 'exists:students,id'],
            'report_date' => ['required', 'date'],
            'meeting_number' => ['required', 'integer', 'min:1'],
            'materi' => ['required', 'string'],
            'behavior' => ['required', 'string'],
            'language' => ['nullable', 'string', 'in:id,en'],
        ];
    }
}
