<?php

namespace Database\Factories;

use App\Enums\RiskAlertReason;
use App\Models\Cohort;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiskAlert>
 */
class RiskAlertFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'cohort_id' => Cohort::factory(),
            'reason' => fake()->randomElement(RiskAlertReason::cases())->value,
            'detail' => fake()->sentence(),
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
        ]);
    }

    public function reportMissing(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => RiskAlertReason::ReportMissing->value,
        ]);
    }

    public function lowUnderstanding(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => RiskAlertReason::LowUnderstanding->value,
        ]);
    }

    public function lowScore(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => RiskAlertReason::LowScore->value,
        ]);
    }
}
