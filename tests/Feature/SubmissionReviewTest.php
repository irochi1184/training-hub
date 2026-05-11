<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\Curriculum;
use App\Models\Question;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubmissionReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createSubmittedTest(): array
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id, 'max_attempts' => 3]);

        $q1 = Question::factory()->create(['test_id' => $test->id, 'score' => 10, 'position' => 1]);
        Choice::factory()->correct()->create(['question_id' => $q1->id]);
        Choice::factory()->create(['question_id' => $q1->id, 'is_correct' => false]);

        $submission = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 10,
            'attempt' => 1,
        ]);

        return [$student, $test, $submission];
    }

    public function test_受験結果に再受験情報が含まれる(): void
    {
        [$student, $test, $submission] = $this->createSubmittedTest();

        $response = $this->actingAs($student)->get("/submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Submissions/Show')
            ->where('canRetake', true)
            ->where('remainingAttempts', 2) // max 3 - 1 used = 2
        );
    }

    public function test_前回スコアが含まれる(): void
    {
        [$student, $test, $submission1] = $this->createSubmittedTest();

        // 2回目の受験
        $submission2 = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 8,
            'attempt' => 2,
        ]);

        $response = $this->actingAs($student)->get("/submissions/{$submission2->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Submissions/Show')
            ->where('previousScore', 10) // 1回目のスコア
        );
    }

    public function test_初回受験では前回スコアがnull(): void
    {
        [$student, $test, $submission] = $this->createSubmittedTest();

        $response = $this->actingAs($student)->get("/submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Submissions/Show')
            ->where('previousScore', null)
        );
    }

    public function test_回数上限に達すると再受験不可(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id, 'max_attempts' => 1]);
        $q = Question::factory()->create(['test_id' => $test->id, 'score' => 10]);
        Choice::factory()->correct()->create(['question_id' => $q->id]);

        $submission = Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
            'score' => 10,
            'attempt' => 1,
        ]);

        $response = $this->actingAs($student)->get("/submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Submissions/Show')
            ->where('canRetake', false)
            ->where('remainingAttempts', 0)
        );
    }

    public function test_他の学生の結果を見ても再受験情報は非表示(): void
    {
        [$student, $test, $submission] = $this->createSubmittedTest();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get("/submissions/{$submission->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Submissions/Show')
            ->where('canRetake', false)
        );
    }
}
