<?php

namespace Tests\Feature;

use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DetectRiskStudentsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_実施中カリキュラムのみに検知を実行する(): void
    {
        $ongoing = Curriculum::factory()->create([
            'starts_on' => Carbon::today()->subDays(10)->toDateString(),
            'ends_on' => Carbon::today()->addDays(10)->toDateString(),
        ]);
        $past = Curriculum::factory()->create([
            'starts_on' => Carbon::today()->subDays(100)->toDateString(),
            'ends_on' => Carbon::today()->subDays(10)->toDateString(),
        ]);

        $studentOngoing = User::factory()->student()->create();
        $studentPast = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $ongoing->id, 'user_id' => $studentOngoing->id]);
        Enrollment::factory()->create(['curriculum_id' => $past->id, 'user_id' => $studentPast->id]);

        // 直近3日間の日報がない状態(ReportMissingが発火する条件)
        DailyReport::factory()->create([
            'user_id' => $studentOngoing->id,
            'curriculum_id' => $ongoing->id,
            'reported_on' => Carbon::today()->subDays(5)->toDateString(),
        ]);
        DailyReport::factory()->create([
            'user_id' => $studentPast->id,
            'curriculum_id' => $past->id,
            'reported_on' => Carbon::today()->subDays(50)->toDateString(),
        ]);

        $this->artisan('risk:detect')->assertExitCode(0);

        // 実施中のカリキュラムには ReportMissing アラートが生成されている
        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $studentOngoing->id,
            'curriculum_id' => $ongoing->id,
            'reason' => RiskAlertReason::ReportMissing->value,
        ]);

        // 過去のカリキュラムにはアラートが生成されていない
        $this->assertDatabaseMissing('risk_alerts', [
            'curriculum_id' => $past->id,
        ]);
    }

    public function test_対象カリキュラムがゼロでも正常終了する(): void
    {
        Curriculum::factory()->create([
            'starts_on' => Carbon::today()->addDays(10)->toDateString(),
            'ends_on' => Carbon::today()->addDays(30)->toDateString(),
        ]);

        $this->artisan('risk:detect')
            ->expectsOutputToContain('0 件')
            ->assertExitCode(0);
    }
}
