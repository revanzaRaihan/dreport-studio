<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required', 
                'uuid', 
                Rule::exists('students', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())->whereNull('deleted_at');
                })
            ],
            'meeting_number' => ['required', 'integer', 'min:1'],
            'report_date' => ['required', 'date'],
            'materi' => ['required', 'string', 'max:1000'],
            'behavior' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'pending_report_id' => ['nullable', 'uuid', 'exists:pending_reports,id'],
            'image' => ['nullable', 'image', 'max:5120'], // Max 5MB
        ];
    }
}
