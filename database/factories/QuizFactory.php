<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Test>
 *
 * クラス名をTestFactoryにするとPHPUnitと衝突するため、QuizFactoryとする。
 * Test::factory() で使うには Model 側で HasFactory を確認すること。
 */
class QuizFactory extends Factory
{
    protected $model = Test::class;

    public function definition(): array
    {
        return [
            'cohort_id' => Cohort::factory(),
            'created_by' => User::factory()->instructor(),
            'title' => fake()->words(4, true) . 'テスト',
            'description' => fake()->optional()->sentence(),
            'time_limit_minutes' => fake()->optional()->numberBetween(10, 60),
            'opens_at' => null,
            'closes_at' => null,
        ];
    }
}
