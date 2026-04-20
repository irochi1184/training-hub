<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'body' => fake()->sentence() . '？',
            'position' => fake()->numberBetween(1, 10),
            'score' => 1,
        ];
    }
}
