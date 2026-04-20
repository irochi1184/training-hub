<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohort_id' => ['required', 'integer', 'exists:cohorts,id'],
            'reported_on' => ['required', 'date'],
            'understanding_level' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string'],
            'impression' => ['nullable', 'string'],
        ];
    }
}
