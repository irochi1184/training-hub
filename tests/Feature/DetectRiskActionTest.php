<?php

namespace Tests\Feature;

use App\Actions\DetectRiskAction;
use App\Enums\RiskAlertReason;
use App\Models\Cohort;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DetectRiskActionTest extends TestCase
{
    use RefreshDatabase;

    /** コホートと受講生のセットを用意するヘルパー */
    private function setupCohortWithStudent(): array
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        $student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'cohort_id' => $cohort->id,
            'user_id' => $student->id,
        ]);

        return [$cohort, $student];
    }

    /** 3日以上日報未提出で report_missing アラートが生成される */
    public function test_3日以上日報未提出でアラートが生成される(): void
    {
        [$cohort, $student] = $this->setupCohortWithStudent();

        // 4日以上前の日報のみ作成（直近3日は未提出）
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'reported_on' => Carbon::today()->subDays(4)->toDateString(),
        ]);

        (new DetectRiskAction())->execute($cohort);

        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'reason' => RiskAlertReason::ReportMissing->value,
        ]);
    }

    /** 理解度平均 2.0 以下で low_understanding アラートが生成される */
    public function test_理解度平均2以下でアラートが生成される(): void
    {
        [$cohort, $student] = $this->setupCohortWithStudent();

        // 理解度1と2の日報を作成（平均1.5）
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'understanding_level' => 1,
            'reported_on' => Carbon::today()->toDateString(),
        ]);
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'understanding_level' => 2,
            'reported_on' => Carbon::today()->subDay()->toDateString(),
        ]);

        (new DetectRiskAction())->execute($cohort);

        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
        ]);
    }

    /** テスト平均得点率 50% 以下で low_score アラートが生成される */
    public function test_テスト平均得点率50以下でアラートが生成される(): void
    {
        [$cohort, $student] = $this->setupCohortWithStudent();

        // 満点10点のテストで3点しか取れていない（30%）
        $test = Test::factory()->create(['cohort_id' => $cohort->id]);
        Question::factory()->count(10)->create(['test_id' => $test->id, 'score' => 1]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'reported_on' => Carbon::today()->toDateString(),
        ]);

        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 3,
        ]);

        (new DetectRiskAction())->execute($cohort);

        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'reason' => RiskAlertReason::LowScore->value,
        ]);
    }

    /** 既存の未解消アラートと重複しない */
    public function test_既存の未解消アラートと重複しない(): void
    {
        [$cohort, $student] = $this->setupCohortWithStudent();

        // 未解消の report_missing アラートが既に存在する
        RiskAlert::factory()->reportMissing()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'resolved_at' => null,
        ]);

        (new DetectRiskAction())->execute($cohort);

        // アラートが1件のままであること
        $this->assertSame(1, RiskAlert::where('user_id', $student->id)
            ->where('cohort_id', $cohort->id)
            ->where('reason', RiskAlertReason::ReportMissing->value)
            ->count());
    }

    /** 解消済みアラートがあっても新規に作成される */
    public function test_解消済みアラートがあっても新規に作成される(): void
    {
        [$cohort, $student] = $this->setupCohortWithStudent();

        // 解消済みの report_missing アラートが存在する
        RiskAlert::factory()->reportMissing()->resolved()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
        ]);

        // 直近3日の日報がない状態で再検知
        (new DetectRiskAction())->execute($cohort);

        // 解消済みの分とは別に新規アラートが作成されること
        $this->assertSame(2, RiskAlert::where('user_id', $student->id)
            ->where('cohort_id', $cohort->id)
            ->where('reason', RiskAlertReason::ReportMissing->value)
            ->count());
    }
}
