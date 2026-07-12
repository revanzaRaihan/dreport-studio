<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'subject'       => ['required', 'string', 'max:255'],
            'meeting_count' => ['required', 'integer', 'min:0'],
            'first_meeting_date' => ['nullable', 'date'],
        ];
    }
}
