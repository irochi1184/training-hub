<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentProgressDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_学生ダッシュボードにサマリーカードデータが含まれる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([User::factory()->instructor()->create()->id => ['role' => 'main']]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        // 日報を5件作成（30日中）
        for ($i = 0; $i < 5; $i++) {
            DailyReport::factory()->create([
                'user_id' => $student->id,
                'curriculum_id' => $curriculum->id,
                'reported_on' => Carbon::today()->subDays($i),
            ]);
        }

        // テストと受験を作成
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        $question = Question::factory()->create(['test_id' => $test->id, 'score' => 10]);
        Choice::factory()->correct()->create(['question_id' => $question->id]);

        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 8,
            'attempt' => 1,
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('studentStats.report_rate', 17) // 5/30 = 16.7 → 17
            ->where('studentStats.test_avg_score', 8)
            ->where('studentStats.test_count', 1)
        );
    }

    public function test_学生ダッシュボードに成績推移データが含まれる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([User::factory()->instructor()->create()->id => ['role' => 'main']]);

        $test = Test::factory()->create(['curriculum_id' => $curriculum->id, 'title' => 'テストA']);
        $question = Question::factory()->create(['test_id' => $test->id, 'score' => 10]);
        Choice::factory()->correct()->create(['question_id' => $question->id]);

        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 7,
            'attempt' => 1,
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('scoreTrend', 1)
            ->where('scoreTrend.0.test_title', 'テストA')
            ->where('scoreTrend.0.score', 7)
            ->where('scoreTrend.0.total_points', 10)
        );
    }

    public function test_学生ダッシュボードにカリキュラム別進捗が含まれる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create(['name' => 'PHP基礎']);
        $curriculum->instructors()->syncWithoutDetaching([User::factory()->instructor()->create()->id => ['role' => 'main']]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        // 2つテストを作成、1つだけ受験
        $test1 = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        $q1 = Question::factory()->create(['test_id' => $test1->id, 'score' => 10]);
        Choice::factory()->correct()->create(['question_id' => $q1->id]);

        Test::factory()->create(['curriculum_id' => $curriculum->id]);

        Submission::factory()->submitted()->create([
            'test_id' => $test1->id,
            'user_id' => $student->id,
            'score' => 9,
            'attempt' => 1,
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('curriculumProgress', 1)
            ->where('curriculumProgress.0.curriculum_name', 'PHP基礎')
            ->where('curriculumProgress.0.total_tests', 2)
            ->where('curriculumProgress.0.taken_tests', 1)
            ->where('curriculumProgress.0.avg_score', 9)
        );
    }

    public function test_学生ダッシュボードに直近の活動が含まれる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([User::factory()->instructor()->create()->id => ['role' => 'main']]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => Carbon::today(),
        ]);

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('recentActivities')
            ->where('recentActivities.0.type', 'report')
        );
    }

    public function test_受験記録がない学生でもダッシュボードが表示される(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->where('studentStats.test_count', 0)
            ->where('studentStats.test_avg_score', null)
            ->has('scoreTrend', 0)
            ->has('curriculumProgress', 0)
            ->has('recentActivities', 0)
        );
    }
}
