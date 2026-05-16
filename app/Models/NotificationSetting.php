<?php

namespace App\Models;

use App\Enums\NotificationEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'event_type',
        'enabled',
        'channel',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => NotificationEventType::class,
            'enabled' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
