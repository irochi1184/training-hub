<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    // PHPUnitのTestCaseと名前衝突を避けるためQuizFactoryを使用する
    protected static function newFactory(): QuizFactory
    {
        return QuizFactory::new();
    }

    protected $fillable = [
        'curriculum_id',
        'created_by',
        'title',
        'description',
        'time_limit_minutes',
        'opens_at',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function hasSubmissions(): bool
    {
        return $this->submissions()->exists();
    }

    public function isAvailableFor(\DateTimeInterface $at): bool
    {
        if ($this->opens_at && $this->opens_at->gt($at)) {
            return false;
        }
        if ($this->closes_at && $this->closes_at->lt($at)) {
            return false;
        }

        return true;
    }
}
