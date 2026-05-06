<?php

namespace Database\Factories;

use App\Models\Curriculum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curriculum>
 */
class CurriculumFactory extends Factory
{
    protected $model = Curriculum::class;

    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 year', 'now');
        $endsOn = fake()->dateTimeBetween($startsOn, '+1 year');

        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['IT研修', 'ロジック研修【Java】', 'Webアプリ開発', 'データ分析入門']),
            'starts_on' => $startsOn->format('Y-m-d'),
            'ends_on' => $endsOn->format('Y-m-d'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Curriculum $curriculum) {
            // メイン講師が未登録なら自動で1人アタッチ
            if ($curriculum->instructors()->count() === 0) {
                $instructor = User::factory()->instructor()->create([
                    'organization_id' => $curriculum->organization_id,
                ]);
                $curriculum->instructors()->attach($instructor->id, ['role' => 'main']);
            }
        });
    }
}
