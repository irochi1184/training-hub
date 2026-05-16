<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slack_webhook_url' => null,
        ];
    }

    /** Slack Webhook URL が設定済みの状態 */
    public function withSlack(): static
    {
        return $this->state(['slack_webhook_url' => 'https://hooks.slack.com/services/TEST/WEBHOOK']);
    }
}
