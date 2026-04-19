<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Model
{
    protected $fillable = [
        'user_id',
        'cohort_id',
        'reported_on',
        'understanding_level',
        'content',
        'impression',
    ];

    protected function casts(): array
    {
        return [
            'reported_on' => 'date',
            'understanding_level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DailyReportComment::class);
    }
}
