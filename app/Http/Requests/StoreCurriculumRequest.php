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
        $instructorExists = Rule::exists('users', 'id')->where(
            fn ($q) => $q->where('role', UserRole::Instructor->value)
        );

        return [
            'name' => ['required', 'string', 'max:255'],
            'main_instructor_ids' => ['required', 'array', 'min:1'],
            'main_instructor_ids.*' => ['required', 'integer', $instructorExists],
            'sub_instructor_ids' => ['nullable', 'array'],
            'sub_instructor_ids.*' => ['required', 'integer', $instructorExists],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_on.after_or_equal' => '終了日は開始日以降の日付を指定してください',
            'main_instructor_ids.required' => 'メイン講師を1名以上選択してください',
            'main_instructor_ids.min' => 'メイン講師を1名以上選択してください',
            'main_instructor_ids.*.exists' => '指定された講師が存在しません',
            'sub_instructor_ids.*.exists' => '指定された講師が存在しません',
        ];
    }
}
