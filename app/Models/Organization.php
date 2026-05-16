<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slack_webhook_url'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function curricula(): HasMany
    {
        return $this->hasMany(Curriculum::class);
    }

    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    /** Slack Webhook URLが設定されているか */
    public function isSlackEnabled(): bool
    {
        return filled($this->slack_webhook_url);
    }
}
