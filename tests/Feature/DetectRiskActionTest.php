<?php

namespace Tests\Feature;

use App\Actions\DetectRiskAction;
use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
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

    /** カリキュラムと受講生のセットを用意するヘルパー */
    private function setupCurriculumWithStudent(): array
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
        ]);

        return [$curriculum, $student];
    }

    /** 3日以上日報未提出で report_missing アラートが生成される */
    public function test_3日以上日報未提出でアラートが生成される(): void
    {
        [$curriculum, $student] = $this->setupCurriculumWithStudent();

        // 4日以上前の日報のみ作成（直近3日は未提出）
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => Carbon::today()->subDays(4)->toDateString(),
        ]);

        (new DetectRiskAction())->execute($curriculum);

        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reason' => RiskAlertReason::ReportMissing->value,
        ]);
    }

    /** 理解度平均 2.0 以下で low_understanding アラートが生成される */
    public function test_理解度平均2以下でアラートが生成される(): void
    {
        [$curriculum, $student] = $this->setupCurriculumWithStudent();

        // 理解度1と2の日報を作成（平均1.5）
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'understanding_level' => 1,
            'reported_on' => Carbon::today()->toDateString(),
        ]);
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'understanding_level' => 2,
            'reported_on' => Carbon::today()->subDay()->toDateString(),
        ]);

        (new DetectRiskAction())->execute($curriculum);

        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
        ]);
    }

    /** テスト平均得点率 50% 以下で low_score アラートが生成される */
    public function test_テスト平均得点率50以下でアラートが生成される(): void
    {
        [$curriculum, $student] = $this->setupCurriculumWithStudent();

        // 満点10点のテストで3点しか取れていない（30%）
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        Question::factory()->count(10)->create(['test_id' => $test->id, 'score' => 1]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => Carbon::today()->toDateString(),
        ]);

        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 3,
        ]);

        (new DetectRiskAction())->execute($curriculum);

        $this->assertDatabaseHas('risk_alerts', [
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reason' => RiskAlertReason::LowScore->value,
        ]);
    }

    /** 既存の未解消アラートと重複しない */
    public function test_既存の未解消アラートと重複しない(): void
    {
        [$curriculum, $student] = $this->setupCurriculumWithStudent();

        // 未解消の report_missing アラートが既に存在する
        RiskAlert::factory()->reportMissing()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'resolved_at' => null,
        ]);

        (new DetectRiskAction())->execute($curriculum);

        // アラートが1件のままであること
        $this->assertSame(1, RiskAlert::where('user_id', $student->id)
            ->where('curriculum_id', $curriculum->id)
            ->where('reason', RiskAlertReason::ReportMissing->value)
            ->count());
    }

    /** 解消済みアラートがあっても新規に作成される */
    public function test_解消済みアラートがあっても新規に作成される(): void
    {
        [$curriculum, $student] = $this->setupCurriculumWithStudent();

        // 解消済みの report_missing アラートが存在する
        RiskAlert::factory()->reportMissing()->resolved()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
        ]);

        // 直近3日の日報がない状態で再検知
        (new DetectRiskAction())->execute($curriculum);

        // 解消済みの分とは別に新規アラートが作成されること
        $this->assertSame(2, RiskAlert::where('user_id', $student->id)
            ->where('curriculum_id', $curriculum->id)
            ->where('reason', RiskAlertReason::ReportMissing->value)
            ->count());
    }
}
