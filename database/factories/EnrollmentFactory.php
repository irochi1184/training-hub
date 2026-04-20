<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cohort_id' => Cohort::factory(),
            'user_id' => User::factory()->student(),
            'enrolled_at' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ];
    }
}
