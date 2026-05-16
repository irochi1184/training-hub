<?php

namespace Database\Factories;

use App\Enums\NotificationEventType;
use App\Models\NotificationSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSetting>
 */
class NotificationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'event_type' => fake()->randomElement(NotificationEventType::cases())->value,
            'enabled' => true,
            'channel' => null,
        ];
    }

    /** 通知無効状態 */
    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }
}
