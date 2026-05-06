<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\Curriculum;
use App\Models\Question;
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
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);

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
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
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
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
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
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->delete("/tests/{$test->id}");

        $response->assertRedirect(route('tests.index'));
        $this->assertDatabaseMissing('tests', ['id' => $test->id]);
    }

    /** 受験者がいるテストは削除できない */
    public function test_受験者がいるテストは削除できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
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

    // =========================================================================
    // テスト分析画面 (tests.show)
    // =========================================================================

    /** admin が分析画面にアクセスできる */
    public function test_adminがテスト分析画面にアクセスできる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($admin)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tests/Analytics')
            ->has('test')
            ->has('questionAnalytics')
            ->has('submissions')
            ->has('summary')
        );
    }

    /** 担当 instructor が分析画面にアクセスできる */
    public function test_担当instructorがテスト分析画面にアクセスできる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Tests/Analytics'));
    }

    /** 別担当の instructor は分析画面にアクセスできない */
    public function test_別担当instructorがテスト分析画面にアクセスすると403(): void
    {
        $otherInstructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(); // instructor_id が別ユーザー
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($otherInstructor)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(403);
    }

    /** student は分析画面にアクセスできない（ミドルウェアで弾かれる） */
    public function test_studentがテスト分析画面にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($student)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(403);
    }

    /** 分析画面のサマリーが正しく集計される */
    public function test_テスト分析画面のサマリーが正しく集計される(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        // 問題と選択肢を作成
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        $question = Question::factory()->create(['test_id' => $test->id, 'position' => 1, 'score' => 10]);
        $correctChoice = Choice::factory()->correct()->create(['question_id' => $question->id, 'position' => 1]);
        $wrongChoice = Choice::factory()->create(['question_id' => $question->id, 'position' => 2]);

        // 受験者1：正答（score 10）
        $sub1 = Submission::factory()->submitted()->create(['test_id' => $test->id, 'score' => 10]);
        Answer::factory()->create([
            'submission_id' => $sub1->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice->id,
            'is_correct' => true,
        ]);

        // 受験者2：誤答（score 0）
        $sub2 = Submission::factory()->submitted()->create(['test_id' => $test->id, 'score' => 0]);
        Answer::factory()->create([
            'submission_id' => $sub2->id,
            'question_id' => $question->id,
            'choice_id' => $wrongChoice->id,
            'is_correct' => false,
        ]);

        $response = $this->actingAs($admin)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tests/Analytics')
            ->where('summary.total_submissions', 2)
            ->where('summary.avg_score', 5)
            ->where('summary.max_score', 10)
            ->where('summary.min_score', 0)
            ->where('summary.total_points', 10)
        );
    }

    /** 分析画面の問題別集計が正しく計算される */
    public function test_テスト分析画面の問題別集計が正しく計算される(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        $question = Question::factory()->create(['test_id' => $test->id, 'position' => 1, 'score' => 5]);
        $correctChoice = Choice::factory()->correct()->create(['question_id' => $question->id, 'position' => 1]);
        Choice::factory()->create(['question_id' => $question->id, 'position' => 2]);

        // 3人受験、うち2人が正答
        foreach (range(1, 2) as $_) {
            $sub = Submission::factory()->submitted()->create(['test_id' => $test->id]);
            Answer::factory()->create([
                'submission_id' => $sub->id,
                'question_id' => $question->id,
                'choice_id' => $correctChoice->id,
                'is_correct' => true,
            ]);
        }
        $sub3 = Submission::factory()->submitted()->create(['test_id' => $test->id]);
        Answer::factory()->create([
            'submission_id' => $sub3->id,
            'question_id' => $question->id,
            'choice_id' => $correctChoice->id,
            'is_correct' => true,
        ]);

        $response = $this->actingAs($admin)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tests/Analytics')
            ->has('questionAnalytics', 1)
            ->where('questionAnalytics.0.question_id', $question->id)
            ->where('questionAnalytics.0.position', 1)
            ->where('questionAnalytics.0.score', 5)
            ->where('questionAnalytics.0.total_answers', 3)
            ->where('questionAnalytics.0.correct_count', 3)
            ->where('questionAnalytics.0.correct_rate', 100)
        );
    }

    /** 未受験のテストでは空の集計が返る */
    public function test_受験者なしのテストで空の集計が返る(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);
        Question::factory()->create(['test_id' => $test->id, 'position' => 1, 'score' => 10]);

        $response = $this->actingAs($admin)->get("/tests/{$test->id}/analytics");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tests/Analytics')
            ->where('summary.total_submissions', 0)
            ->where('summary.avg_score', null)
            ->where('summary.max_score', null)
            ->where('summary.min_score', null)
            ->where('summary.total_points', 10)
            ->has('submissions', 0)
        );
    }
}
