<?php

namespace App\Http\Requests\PendingReport;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePendingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id'     => ['required', 'uuid', 'exists:students,id'],
            'meeting_number' => ['required', 'integer', 'min:1'],
            'report_date'    => ['required', 'date'],
        ];
    }
}
