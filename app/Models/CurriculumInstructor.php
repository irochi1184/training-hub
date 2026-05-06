<?php

namespace App\Models;

use App\Enums\CurriculumInstructorRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumInstructor extends Model
{
    protected $fillable = [
        'curriculum_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => CurriculumInstructorRole::class,
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
