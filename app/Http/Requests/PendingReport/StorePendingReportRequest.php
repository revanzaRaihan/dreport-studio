<?php

namespace App\Http\Requests\PendingReport;

use Illuminate\Foundation\Http\FormRequest;

class StorePendingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'     => [
                'required', 
                'uuid', 
                \Illuminate\Validation\Rule::exists('students', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())->whereNull('deleted_at');
                })
            ],
            'meeting_number' => ['required', 'integer', 'min:1'],
            'report_date'    => ['required', 'date'],
        ];
    }
}
