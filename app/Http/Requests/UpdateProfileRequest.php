<?php

namespace App\Http\Requests;

use App\Enums\SkillLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:1000'],
            'learning_goal' => ['nullable', 'string', 'max:500'],
            'skills' => ['array', 'max:10'],
            'skills.*.skill_name' => ['required', 'string', 'max:100'],
            'skills.*.level' => ['required', 'integer', Rule::in([1, 2, 3])],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'bio.max' => '自己紹介は1000文字以内で入力してください',
            'learning_goal.max' => '学習目標は500文字以内で入力してください',
            'skills.max' => 'スキルは最大10個まで登録できます',
            'skills.*.skill_name.required' => 'スキル名は必須です',
            'skills.*.level.required' => 'レベルを選択してください',
        ];
    }
}
