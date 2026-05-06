<?php

namespace App\Models;

use App\Enums\CurriculumInstructorRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Curriculum extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'curricula';

    protected $fillable = [
        'organization_id',
        'name',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'curriculum_instructors')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function mainInstructors(): BelongsToMany
    {
        return $this->instructors()->wherePivot('role', CurriculumInstructorRole::Main->value);
    }

    public function subInstructors(): BelongsToMany
    {
        return $this->instructors()->wherePivot('role', CurriculumInstructorRole::Sub->value);
    }

    public function hasInstructor(int $userId): bool
    {
        return $this->instructors()->where('users.id', $userId)->exists();
    }

    public function curriculumInstructors(): HasMany
    {
        return $this->hasMany(CurriculumInstructor::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function dailyReports(): HasMany
    {
        return $this->hasMany(DailyReport::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function riskAlerts(): HasMany
    {
        return $this->hasMany(RiskAlert::class);
    }
}
