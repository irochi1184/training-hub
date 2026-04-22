<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
class DailyReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'curriculum_id' => Curriculum::factory(),
            'reported_on' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'understanding_level' => fake()->numberBetween(1, 5),
            'content' => fake()->paragraphs(2, true),
            'impression' => fake()->optional()->sentence(),
        ];
    }
}
