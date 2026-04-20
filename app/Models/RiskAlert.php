<?php

namespace App\Models;

use App\Enums\RiskAlertReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cohort_id',
        'reason',
        'detail',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'reason' => RiskAlertReason::class,
            'resolved_at' => 'datetime',
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

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
