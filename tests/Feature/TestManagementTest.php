<?php

namespace Tests\Feature;

use App\Models\Curriculum;
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
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);

        $response = $this->actingAs($instructor)->post('/tests', [
            'curriculum_id' => $curriculum->id,
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
            'curriculum_id' => $curriculum->id,
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
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        // 受験者を作成する
        Submission::factory()->create(['test_id' => $test->id]);

        $response = $this->actingAs($instructor)->put("/tests/{$test->id}", [
            'curriculum_id' => $curriculum->id,
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
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->put("/tests/{$test->id}", [
            'curriculum_id' => $curriculum->id,
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

    /** student がテストを作成しようとすると 403 */
    public function test_studentがテストを作成しようとすると403(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();

        $response = $this->actingAs($student)->post('/tests', [
            'curriculum_id' => $curriculum->id,
            'title' => '不正作成テスト',
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
        $this->assertDatabaseMissing('tests', ['title' => '不正作成テスト']);
    }

    /** instructor が受験者なしのテストを削除できる */
    public function test_instructorがテストを削除できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->delete("/tests/{$test->id}");

        $response->assertRedirect(route('tests.index'));
        $this->assertDatabaseMissing('tests', ['id' => $test->id]);
    }

    /** 受験者がいるテストは削除できない */
    public function test_受験者がいるテストは削除できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        // 受験者を作成する
        Submission::factory()->create(['test_id' => $test->id]);

        $response = $this->actingAs($instructor)->delete("/tests/{$test->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tests', ['id' => $test->id]);
    }

    /** student が公開期間外のテストの受験画面にアクセスすると 403 */
    public function test_studentが公開期間外テストの受験画面にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();

        // closes_at を過去に設定して期間外にする
        $test = Test::factory()->create([
            'curriculum_id' => $curriculum->id,
            'opens_at' => now()->subDays(10),
            'closes_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertStatus(403);
    }
}
