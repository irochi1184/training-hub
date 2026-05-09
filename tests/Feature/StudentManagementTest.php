<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    /** admin が受講生一覧を閲覧できる */
    public function test_adminが受講生一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Students/Index'));
    }

    /** instructor が担当カリキュラムの受講生を閲覧できる */
    public function test_instructorが担当カリキュラムの受講生を閲覧できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        User::factory()->student()->count(3)->create()->each(function (User $student) use ($curriculum) {
            Enrollment::factory()->create([
                'user_id' => $student->id,
                'curriculum_id' => $curriculum->id,
            ]);
        });

        $response = $this->actingAs($instructor)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Students/Index'));
    }

    /** student が受講生一覧にアクセスすると 403 */
    public function test_studentが受講生一覧にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/students');

        $response->assertStatus(403);
    }

    /** admin が受講生詳細を閲覧できる */
    public function test_adminが受講生詳細を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($admin)->get("/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Students/Show'));
    }

    /** 受講生詳細にスコア推移データが含まれる */
    public function test_受講生詳細にスコア推移データが含まれる(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$admin->id => ['role' => 'main']]);

        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        $question = Question::factory()->create(['test_id' => $test->id, 'score' => 10]);
        Choice::factory()->correct()->create(['question_id' => $question->id]);

        // 受験を2回作成
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 8,
            'attempt' => 1,
        ]);
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 10,
            'attempt' => 2,
        ]);

        $response = $this->actingAs($admin)->get("/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students/Show')
            ->has('scoreTrend', 2)
            ->where('scoreTrend.0.score', 8)
            ->where('scoreTrend.0.total_points', 10)
            ->where('scoreTrend.1.score', 10)
        );
    }

    /** 受験記録がない場合はスコア推移が空配列 */
    public function test_受験記録がない場合スコア推移が空(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($admin)->get("/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students/Show')
            ->has('scoreTrend', 0)
        );
    }
}
