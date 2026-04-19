<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cohort_id' => ['required', 'integer', 'exists:cohorts,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.body' => ['required', 'string'],
            'questions.*.score' => ['required', 'integer', 'min:1'],
            'questions.*.choices' => ['required', 'array', 'min:2'],
            'questions.*.choices.*.body' => ['required', 'string'],
            'questions.*.choices.*.is_correct' => ['required', 'boolean'],
        ];
    }
}
