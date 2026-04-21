<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\Cohort;
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
    private function createTestWithQuestions(Cohort $cohort): array
    {
        $test = Test::factory()->create([
            'cohort_id' => $cohort->id,
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
        $cohort = Cohort::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'cohort_id' => $cohort->id]);

        [$test] = $this->createTestWithQuestions($cohort);

        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Tests/Take'));
    }

    /** student がテストを提出すると自動採点される */
    public function test_studentがテストを提出すると自動採点される(): void
    {
        $student = User::factory()->student()->create();
        $cohort = Cohort::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'cohort_id' => $cohort->id]);

        [$test, $question, $correctChoice] = $this->createTestWithQuestions($cohort);

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

    /** 同じテストを2回受験しようとすると既存の提出画面にリダイレクトされる */
    public function test_受験済みのテストを再度受験しようとするとリダイレクトされる(): void
    {
        $student = User::factory()->student()->create();
        $cohort = Cohort::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'cohort_id' => $cohort->id]);

        [$test, $question, $correctChoice] = $this->createTestWithQuestions($cohort);

        // 受験済みの提出を作成する
        $submission = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
        ]);

        // 再提出を試みる（Policy: create は未受験のみ許可）
        $response = $this->actingAs($student)->post("/tests/{$test->id}/submissions", [
            'answers' => [
                ['question_id' => $question->id, 'choice_id' => $correctChoice->id],
            ],
        ]);

        // 既存の提出ページにリダイレクトされる
        $response->assertRedirect(route('submissions.show', $submission));
    }

    /** 受験済みのテスト受験画面(GET)にアクセスすると結果ページへリダイレクトされる */
    public function test_受験済みのテスト受験画面にアクセスすると結果ページへリダイレクトされる(): void
    {
        $student = User::factory()->student()->create();
        $cohort = Cohort::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'cohort_id' => $cohort->id]);

        [$test] = $this->createTestWithQuestions($cohort);

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
        $cohort = Cohort::factory()->create();
        Enrollment::factory()->create(['user_id' => $student->id, 'cohort_id' => $cohort->id]);

        [$test] = $this->createTestWithQuestions($cohort);

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
}
