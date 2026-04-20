<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DetectRiskStudentsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** コマンドが正常終了する */
    public function test_コマンドが正常終了する(): void
    {
        $this->artisan('risk:detect')
            ->assertExitCode(0);
    }

    /** 対象コホートがない場合はその旨を出力して終了する */
    public function test_対象コホートが存在しない場合はメッセージを出力する(): void
    {
        $this->artisan('risk:detect')
            ->expectsOutput('対象コホートが存在しません。')
            ->assertExitCode(0);
    }

    /** 実施中コホートのみが対象になる */
    public function test_実施中コホートのみ処理される(): void
    {
        $instructor = User::factory()->instructor()->create();

        // 実施中コホート（本日がstarts_on〜ends_onの範囲内）
        $activeCohort = Cohort::factory()->create([
            'instructor_id' => $instructor->id,
            'starts_on' => Carbon::today()->subDays(10),
            'ends_on' => Carbon::today()->addDays(10),
        ]);

        // 終了済みコホート
        Cohort::factory()->create([
            'instructor_id' => $instructor->id,
            'starts_on' => Carbon::today()->subDays(30),
            'ends_on' => Carbon::today()->subDays(1),
        ]);

        $student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'cohort_id' => $activeCohort->id,
            'user_id' => $student->id,
        ]);

        $this->artisan('risk:detect')
            ->expectsOutputToContain('対象コホート: 1件')
            ->assertExitCode(0);
    }

    /** 新規アラートが生成された件数が出力に含まれる */
    public function test_新規アラート件数が出力される(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create([
            'instructor_id' => $instructor->id,
            'starts_on' => Carbon::today()->subDays(10),
            'ends_on' => Carbon::today()->addDays(10),
        ]);

        $student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'cohort_id' => $cohort->id,
            'user_id' => $student->id,
        ]);

        // 直近3日の日報なし → report_missing アラートが発生する想定
        $this->artisan('risk:detect')
            ->expectsOutputToContain('検知完了')
            ->assertExitCode(0);
    }

    /** 既存の未解消アラートがあっても重複して生成しない */
    public function test_既存の未解消アラートが重複しない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create([
            'instructor_id' => $instructor->id,
            'starts_on' => Carbon::today()->subDays(10),
            'ends_on' => Carbon::today()->addDays(10),
        ]);

        $student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'cohort_id' => $cohort->id,
            'user_id' => $student->id,
        ]);

        // 事前に未解消アラートを1件作成
        RiskAlert::factory()->reportMissing()->create([
            'user_id' => $student->id,
            'cohort_id' => $cohort->id,
            'resolved_at' => null,
        ]);

        $alertCountBefore = RiskAlert::where('cohort_id', $cohort->id)
            ->whereNull('resolved_at')
            ->count();

        $this->artisan('risk:detect')->assertExitCode(0);

        // report_missing は既に存在するので増えていないこと
        $this->assertSame(
            $alertCountBefore,
            RiskAlert::where('cohort_id', $cohort->id)
                ->whereNull('resolved_at')
                ->where('reason', 'report_missing')
                ->count(),
        );
    }
}
