<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->words(3, true) . 'コース',
            'description' => fake()->optional()->sentence(),
        ];
    }
}
