<?php

namespace App\Models;

use App\Enums\SummaryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

class AiSummary extends Model
{
    protected $fillable = [
        'organization_id',
        'summarizable_type',
        'summarizable_id',
        'summary_type',
        'content',
        'week_start',
        'week_end',
    ];

    protected function casts(): array
    {
        return [
            'summary_type' => SummaryType::class,
            'week_start' => 'date',
            'week_end' => 'date',
        ];
    }

    public function summarizable(): MorphTo
    {
        return $this->morphTo();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeForWeek(Builder $query, Carbon $weekStart): Builder
    {
        return $query->where('week_start', $weekStart->toDateString());
    }

    public function scopeByType(Builder $query, SummaryType $type): Builder
    {
        return $query->where('summary_type', $type->value);
    }
}
