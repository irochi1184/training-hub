<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'user_id' => User::factory()->student(),
            'started_at' => Carbon::now()->subMinutes(30),
            'submitted_at' => null,
            'score' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'submitted_at' => Carbon::now(),
            'score' => fake()->numberBetween(0, 10),
        ]);
    }
}
