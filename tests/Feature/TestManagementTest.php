<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TestManagementTest extends TestCase
{
    use RefreshDatabase;

    /** instructor がテスト（questions + choices 含む）を作成できる */
    public function test_instructorがテストを作成できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);

        $response = $this->actingAs($instructor)->post('/tests', [
            'cohort_id' => $cohort->id,
            'title' => 'PHPの基礎テスト',
            'description' => null,
            'time_limit_minutes' => null,
            'opens_at' => null,
            'closes_at' => null,
            'questions' => [
                [
                    'body' => 'PHPの変数定義に使う記号は？',
                    'score' => 1,
                    'choices' => [
                        ['body' => '$', 'is_correct' => true],
                        ['body' => '#', 'is_correct' => false],
                        ['body' => '@', 'is_correct' => false],
                        ['body' => '!', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('tests.index'));
        $this->assertDatabaseHas('tests', [
            'cohort_id' => $cohort->id,
            'title' => 'PHPの基礎テスト',
        ]);
        $this->assertDatabaseHas('questions', ['body' => 'PHPの変数定義に使う記号は？']);
        $this->assertDatabaseHas('choices', ['body' => '$', 'is_correct' => true]);
    }

    /** テスト一覧が表示される */
    public function test_テスト一覧を表示できる(): void
    {
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($instructor)->get('/tests');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Tests/Index'));
    }

    /** 受験者がいるテストは更新できない */
    public function test_受験者がいるテストは更新できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        $test = Test::factory()->create(['cohort_id' => $cohort->id]);

        // 受験者を作成する
        Submission::factory()->create(['test_id' => $test->id]);

        $response = $this->actingAs($instructor)->put("/tests/{$test->id}", [
            'cohort_id' => $cohort->id,
            'title' => '更新後タイトル',
            'questions' => [
                [
                    'body' => '問題文',
                    'score' => 1,
                    'choices' => [
                        ['body' => '選択肢A', 'is_correct' => true],
                        ['body' => '選択肢B', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    /** 受験者がいないテストは更新できる */
    public function test_受験者がいないテストは更新できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        $test = Test::factory()->create(['cohort_id' => $cohort->id]);

        $response = $this->actingAs($instructor)->put("/tests/{$test->id}", [
            'cohort_id' => $cohort->id,
            'title' => '更新後タイトル',
            'questions' => [
                [
                    'body' => '問題文',
                    'score' => 1,
                    'choices' => [
                        ['body' => '選択肢A', 'is_correct' => true],
                        ['body' => '選択肢B', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('tests.index'));
        $this->assertDatabaseHas('tests', [
            'id' => $test->id,
            'title' => '更新後タイトル',
        ]);
    }
}
