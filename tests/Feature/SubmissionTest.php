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

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** テストと問題・選択肢のセットを用意するヘルパー */
    private function createTestWithQuestions(Curriculum $curriculum): array
    {
        $test = Test::factory()->create([
            'curriculum_id' => $curriculum->id,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        $question = Question::factory()->create([
            'test_id' => $test->id,
            'score' => 1,
        ]);

        $correctChoice = Choice::factory()->correct()->create(['question_id' => $question->id]);
        $wrongChoice = Choice::factory()->create(['question_id' => $question->id]);

        return [$test, $question, $correctChoice, $wrongChoice];
    }

    /** student がテスト受験画面を表示できる */
    public function test_studentがテスト受験画面を表示できる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test] = $this->createTestWithQuestions($curriculum);

        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Tests/Take'));
    }

    /** student がテストを提出すると自動採点される */
    public function test_studentがテストを提出すると自動採点される(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test, $question, $correctChoice] = $this->createTestWithQuestions($curriculum);

        // take アクセスで Submission を事前生成する
        $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response = $this->actingAs($student)->post("/tests/{$test->id}/submissions", [
            'answers' => [
                ['question_id' => $question->id, 'choice_id' => $correctChoice->id],
            ],
        ]);

        $response->assertRedirect();

        $submission = Submission::where('test_id', $test->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertNotNull($submission->submitted_at);
        $this->assertSame(1, $submission->score);
    }

    /** テスト結果を閲覧できる */
    public function test_テスト結果を閲覧できる(): void
    {
        $student = User::factory()->student()->create();
        $submission = Submission::factory()->submitted()->create(['user_id' => $student->id]);

        $response = $this->actingAs($student)->get("/submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Submissions/Show'));
    }

    /** 同じテストを2回受験しようとすると404になる（受験中のsubmissionがないため） */
    public function test_受験済みのテストを再度受験しようとすると404になる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test, $question, $correctChoice] = $this->createTestWithQuestions($curriculum);

        // 受験済みの提出を作成する（max_attempts=null → 再受験不可）
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
        ]);

        // 再提出を試みる（受験中のsubmissionがないため404）
        $response = $this->actingAs($student)->post("/tests/{$test->id}/submissions", [
            'answers' => [
                ['question_id' => $question->id, 'choice_id' => $correctChoice->id],
            ],
        ]);

        $response->assertNotFound();
    }

    /** 受験済みのテスト受験画面(GET)にアクセスすると結果ページへリダイレクトされる */
    public function test_受験済みのテスト受験画面にアクセスすると結果ページへリダイレクトされる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test] = $this->createTestWithQuestions($curriculum);

        // 受験済みの提出を作成する
        $submission = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
        ]);

        // 受験画面にアクセスすると結果ページへリダイレクトされる
        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertRedirect(route('submissions.show', $submission));
    }

    /** 受験中のテスト受験画面に再アクセスすると継続できる */
    public function test_受験中のテストに再アクセスすると継続できる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test] = $this->createTestWithQuestions($curriculum);

        // 受験中（未提出）の提出を作成する
        $submission = Submission::factory()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'submitted_at' => null,
        ]);

        // 受験画面に再アクセスすると受験画面が表示される（リダイレクトなし）
        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Tests/Take'));

        // 提出レコードが増えていないこと
        $this->assertDatabaseCount('submissions', 1);
    }

    /** max_attempts=3 のテストで再受験できる */
    public function test_再受験可能なテストで制限内なら再受験できる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test, $question, $correctChoice] = $this->createTestWithQuestions($curriculum);
        $test->update(['max_attempts' => 3]);

        // 1回目の提出済み
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt' => 1,
            'score' => 0,
        ]);

        // 再受験画面にアクセスできる
        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Tests/Take'));

        // attempt=2 の提出が作成される
        $this->assertDatabaseHas('submissions', [
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt' => 2,
        ]);
    }

    /** max_attempts の上限に達すると再受験できない */
    public function test_上限に達すると再受験できない(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test] = $this->createTestWithQuestions($curriculum);
        $test->update(['max_attempts' => 2]);

        // 2回分の提出済み
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt' => 1,
        ]);
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt' => 2,
        ]);

        // 再受験画面にアクセスすると403
        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");
        $response->assertForbidden();
    }

    /** max_attempts=0 は無制限に再受験できる */
    public function test_無制限再受験ができる(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        [$test] = $this->createTestWithQuestions($curriculum);
        $test->update(['max_attempts' => 0]);

        // 5回分の提出済み
        for ($i = 1; $i <= 5; $i++) {
            Submission::factory()->submitted()->create([
                'test_id' => $test->id,
                'user_id' => $student->id,
                'attempt' => $i,
            ]);
        }

        // まだ受験できる
        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");
        $response->assertStatus(200);
    }

    /** 結果画面で受験履歴が表示される */
    public function test_結果画面で受験履歴と最高点が返される(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        [$test] = $this->createTestWithQuestions($curriculum);

        $sub1 = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt' => 1,
            'score' => 3,
        ]);
        $sub2 = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'attempt' => 2,
            'score' => 8,
        ]);

        $response = $this->actingAs($student)->get("/submissions/{$sub2->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Submissions/Show')
            ->has('allAttempts', 2)
            ->where('bestScore', 8)
        );
    }
}
