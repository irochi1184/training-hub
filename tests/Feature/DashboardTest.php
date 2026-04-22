<?php

namespace Tests\Feature;

use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\RiskAlert;
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
        );
    }

    public function test_instructorダッシュボードは担当カリキュラムのみに絞られる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $myCurriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
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
