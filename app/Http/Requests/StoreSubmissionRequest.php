<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.choice_id' => ['nullable', 'integer', 'exists:choices,id'],
            'answers.*.choice_ids' => ['nullable', 'array'],
            'answers.*.choice_ids.*' => ['integer', 'exists:choices,id'],
        ];
    }
}
