<?php

namespace Tests\Feature;

use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test as TestModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_adminダッシュボードにカリキュラム別サマリと直近アラートが表示される(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculumA = Curriculum::factory()->create(['name' => 'IT研修']);
        $curriculumB = Curriculum::factory()->create(['name' => 'ロジック研修']);

        $student = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $curriculumA->id, 'user_id' => $student->id]);

        RiskAlert::factory()->create([
            'curriculum_id' => $curriculumA->id,
            'user_id' => $student->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
            'resolved_at' => null,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('adminStats', fn (Assert $stats) => $stats
                ->where('risk_alert_count', 1)
                ->etc()
            )
            ->has('recentRiskAlerts', 1, fn (Assert $alert) => $alert
                ->where('curriculum_name', 'IT研修')
                ->where('user_name', $student->name)
                ->etc()
            )
            ->has('curriculumSummaries', 2)
            ->has('understandingDistribution', 2)
            ->has('reportRateTrend', 7)
            ->has('curriculumScoreComparison', 2)
        );
    }

    public function test_adminダッシュボードの理解度分布にレベル別件数が含まれる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create(['name' => 'IT研修']);
        $student = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $curriculum->id, 'user_id' => $student->id]);

        // 直近7日以内にレベル3の日報を2件作成
        DailyReport::factory()->create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
            'reported_on' => Carbon::today()->subDays(1)->toDateString(),
            'understanding_level' => 3,
        ]);
        DailyReport::factory()->create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
            'reported_on' => Carbon::today()->toDateString(),
            'understanding_level' => 5,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('understandingDistribution', 1, fn (Assert $dist) => $dist
                ->where('curriculum_name', 'IT研修')
                ->where('levels', [0, 0, 1, 0, 1]) // レベル3が1件、レベル5が1件
            )
        );
    }

    public function test_adminダッシュボードの日報提出率推移が7日分返る(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $student = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $curriculum->id, 'user_id' => $student->id]);

        DailyReport::factory()->create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
            'reported_on' => Carbon::today()->toDateString(),
            'understanding_level' => 3,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('reportRateTrend', 7, fn (Assert $item) => $item
                ->has('date')
                ->has('rate')
            )
        );
    }

    public function test_adminダッシュボードのカリキュラム別平均点比較が返る(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create(['name' => 'IT研修']);
        $student = User::factory()->student()->create();
        $test = TestModel::factory()->create(['curriculum_id' => $curriculum->id]);

        Submission::factory()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'submitted_at' => Carbon::now(),
            'score' => 80,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('curriculumScoreComparison', 1, fn (Assert $item) => $item
                ->where('curriculum_name', 'IT研修')
                ->where('avg_score', 80)
            )
        );
    }

    public function test_instructorダッシュボードは担当カリキュラムのみに絞られる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $myCurriculum = Curriculum::factory()->create();
        $myCurriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $otherCurriculum = Curriculum::factory()->create();

        $myStudent = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $myCurriculum->id, 'user_id' => $myStudent->id]);

        $otherStudent = User::factory()->student()->create();
        RiskAlert::factory()->create([
            'curriculum_id' => $otherCurriculum->id,
            'user_id' => $otherStudent->id,
            'resolved_at' => null,
        ]);
        RiskAlert::factory()->create([
            'curriculum_id' => $myCurriculum->id,
            'user_id' => $myStudent->id,
            'resolved_at' => null,
        ]);

        $response = $this->actingAs($instructor)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('instructorStats', fn (Assert $stats) => $stats
                ->where('risk_alert_count', 1)
                ->etc()
            )
            ->has('recentRiskAlerts', 1)
            ->has('curriculumSummaries', 1, fn (Assert $summary) => $summary
                ->where('id', $myCurriculum->id)
                ->etc()
            )
            ->has('understandingDistribution', 1)
            ->has('reportRateTrend', 7)
            ->has('curriculumScoreComparison', 1)
        );
    }

    public function test_instructorダッシュボードの日報提出率は担当受講生のみを母数とする(): void
    {
        $instructor = User::factory()->instructor()->create();
        $myCurriculum = Curriculum::factory()->create();
        $myCurriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $otherCurriculum = Curriculum::factory()->create();

        $myStudent = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $myCurriculum->id, 'user_id' => $myStudent->id]);

        // 担当外カリキュラムの受講生が今日提出しても instructor の集計に混入しないことを確認
        $otherStudent = User::factory()->student()->create();
        Enrollment::factory()->create(['curriculum_id' => $otherCurriculum->id, 'user_id' => $otherStudent->id]);
        DailyReport::factory()->create([
            'curriculum_id' => $otherCurriculum->id,
            'user_id' => $otherStudent->id,
            'reported_on' => Carbon::today()->toDateString(),
            'understanding_level' => 3,
        ]);

        $response = $this->actingAs($instructor)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('reportRateTrend', 7)
        );
    }

    public function test_studentダッシュボードに理解度トレンドが7日分含まれる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['curriculum_id' => $curriculum->id, 'user_id' => $student->id]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => Carbon::today()->subDays(2)->toDateString(),
            'understanding_level' => 4,
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('understandingTrend', 7)
        );
    }

    public function test_studentダッシュボードは本日未提出を検知する(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('studentStats', fn (Assert $stats) => $stats
                ->where('has_missing_report', true)
                ->etc()
            )
        );
    }
}
