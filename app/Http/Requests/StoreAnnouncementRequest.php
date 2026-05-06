<?php

namespace App\Http\Requests;

use App\Enums\AnnouncementPriority;
use App\Enums\AnnouncementTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(AnnouncementPriority::class)],
            'target_type' => ['required', Rule::enum(AnnouncementTargetType::class)],
            'target_id' => ['nullable', 'integer', 'required_unless:target_type,all'],
            'publish_now' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です',
            'body.required' => '本文は必須です',
            'target_id.required_unless' => '対象を選択してください',
        ];
    }
}
