<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instructor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role', UserRole::Instructor->value)),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => '終了日は開始日以降の日付を指定してください',
            'instructor_id.exists' => '指定された講師が存在しません',
        ];
    }
}
