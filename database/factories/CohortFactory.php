<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohort>
 */
class CohortFactory extends Factory
{
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 year', 'now');
        $endsOn = fake()->dateTimeBetween($startsOn, '+1 year');

        return [
            'course_id' => Course::factory(),
            'instructor_id' => User::factory()->instructor(),
            'name' => fake()->year() . '年' . fake()->month() . '月期',
            'starts_on' => $startsOn->format('Y-m-d'),
            'ends_on' => $endsOn->format('Y-m-d'),
        ];
    }
}
