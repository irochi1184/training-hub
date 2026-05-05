<?php

namespace App\Models;

use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkill extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_profile_id',
        'skill_name',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'level' => SkillLevel::class,
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }
}
