<?php

namespace Database\Factories;

use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReportComment>
 */
class DailyReportCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'daily_report_id' => DailyReport::factory(),
            'user_id' => User::factory()->instructor(),
            'body' => fake()->sentence(),
        ];
    }
}
