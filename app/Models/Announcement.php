<?php

namespace App\Models;

use App\Enums\AnnouncementPriority;
use App\Enums\AnnouncementTargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by',
        'title',
        'body',
        'priority',
        'target_type',
        'target_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => AnnouncementPriority::class,
            'target_type' => AnnouncementTargetType::class,
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }
}
