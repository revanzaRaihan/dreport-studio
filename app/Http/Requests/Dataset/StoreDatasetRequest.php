<?php

namespace App\Http\Requests\Dataset;

use Illuminate\Foundation\Http\FormRequest;

class StoreDatasetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'language' => ['required', 'string', 'in:id,en'],
            'section_type' => ['nullable', 'string', 'in:overview,teachers_note,training_recommendation,parent_note'],
            'category' => ['nullable', 'required_if:section_type,training_recommendation', 'string', 'in:kreativitas,logika_terstruktur,eksperimen,coding_dasar'],
        ];
    }
}
