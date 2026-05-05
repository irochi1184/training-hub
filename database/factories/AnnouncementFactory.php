<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'created_by' => User::factory()->admin(),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraphs(2, true),
            'priority' => 'normal',
            'target_type' => 'all',
            'target_id' => null,
            'published_at' => now(),
        ];
    }

    public function important(): static
    {
        return $this->state(fn () => ['priority' => 'important']);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }
}
