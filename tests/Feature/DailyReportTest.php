<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    /** student が日報入力画面を表示できる */
    public function test_studentが日報入力画面を表示できる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($student)->get('/daily-reports/create');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('DailyReports/Create'));
    }

    /** student が日報を提出するとDBに保存されリダイレクトされる */
    public function test_studentが日報を提出できる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($student)->post('/daily-reports', [
            'curriculum_id' => $curriculum->id,
            'reported_on' => today()->toDateString(),
            'understanding_level' => 3,
            'content' => '今日学んだ内容です。',
            'impression' => '楽しかったです。',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'understanding_level' => 3,
        ]);
    }

    /** admin が日報一覧を閲覧できる */
    public function test_adminが日報一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/daily-reports');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('DailyReports/Index'));
    }

    /** admin が日報詳細を閲覧できる */
    public function test_日報詳細を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $report = DailyReport::factory()->create();

        $response = $this->actingAs($admin)->get("/daily-reports/{$report->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('DailyReports/Show'));
    }

    /** 同日・同カリキュラムの日報を再提出すると既存レコードが更新される */
    public function test_同日の日報を再提出すると更新される(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $date = today()->toDateString();

        // 1回目の提出
        $this->actingAs($student)->post('/daily-reports', [
            'curriculum_id' => $curriculum->id,
            'reported_on' => $date,
            'understanding_level' => 3,
            'content' => '最初の内容です。',
        ]);

        // 2回目（同日・同カリキュラム）の提出
        $response = $this->actingAs($student)->post('/daily-reports', [
            'curriculum_id' => $curriculum->id,
            'reported_on' => $date,
            'understanding_level' => 5,
            'content' => '更新後の内容です。',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '日報を更新しました');

        // レコードは1件のみで、内容が更新されていること
        $this->assertDatabaseCount('daily_reports', 1);
        $this->assertDatabaseHas('daily_reports', [
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'understanding_level' => 5,
            'content' => '更新後の内容です。',
        ]);
    }

    /** understanding_level が 0 だとバリデーションエラー */
    public function test_理解度が0だとバリデーションエラーになる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($student)->post('/daily-reports', [
            'curriculum_id' => $curriculum->id,
            'reported_on' => today()->toDateString(),
            'understanding_level' => 0,
            'content' => '内容',
        ]);

        $response->assertSessionHasErrors(['understanding_level']);
    }

    /** understanding_level が 6 だとバリデーションエラー */
    public function test_理解度が6だとバリデーションエラーになる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($student)->post('/daily-reports', [
            'curriculum_id' => $curriculum->id,
            'reported_on' => today()->toDateString(),
            'understanding_level' => 6,
            'content' => '内容',
        ]);

        $response->assertSessionHasErrors(['understanding_level']);
    }
}
