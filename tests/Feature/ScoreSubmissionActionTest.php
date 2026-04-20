<?php

namespace Tests\Feature;

use App\Actions\ScoreSubmissionAction;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreSubmissionActionTest extends TestCase
{
    use RefreshDatabase;

    /** テスト・問題・選択肢・提出を作成するヘルパー */
    private function setupSubmission(int $numQuestions = 2): array
    {
        $student = User::factory()->student()->create();
        $test = Test::factory()->create();

        $questions = [];
        $correctChoices = [];
        $wrongChoices = [];

        for ($i = 0; $i < $numQuestions; $i++) {
            $question = Question::factory()->create([
                'test_id' => $test->id,
                'position' => $i + 1,
                'score' => 1,
            ]);
            $questions[] = $question;
            $correctChoices[] = Choice::factory()->correct()->create(['question_id' => $question->id]);
            $wrongChoices[] = Choice::factory()->create(['question_id' => $question->id]);
        }

        $submission = Submission::factory()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
        ]);

        return [$submission, $questions, $correctChoices, $wrongChoices];
    }

    /** 全問正解で満点になる */
    public function test_全問正解で満点になる(): void
    {
        [$submission, $questions, $correctChoices] = $this->setupSubmission(2);

        foreach ($questions as $i => $question) {
            Answer::factory()->create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'choice_id' => $correctChoices[$i]->id,
            ]);
        }

        (new ScoreSubmissionAction())->execute($submission);

        $this->assertSame(2, $submission->fresh()->score);
    }

    /** 全問不正解で 0 点になる */
    public function test_全問不正解で0点になる(): void
    {
        [$submission, $questions, , $wrongChoices] = $this->setupSubmission(2);

        foreach ($questions as $i => $question) {
            Answer::factory()->create([
                'submission_id' => $submission->id,
                'question_id' => $question->id,
                'choice_id' => $wrongChoices[$i]->id,
            ]);
        }

        (new ScoreSubmissionAction())->execute($submission);

        $this->assertSame(0, $submission->fresh()->score);
    }

    /** 一部正解で正解分のスコアになる */
    public function test_一部正解で正解分のスコアになる(): void
    {
        [$submission, $questions, $correctChoices, $wrongChoices] = $this->setupSubmission(2);

        // 1問目は正解、2問目は不正解
        Answer::factory()->create([
            'submission_id' => $submission->id,
            'question_id' => $questions[0]->id,
            'choice_id' => $correctChoices[0]->id,
        ]);
        Answer::factory()->create([
            'submission_id' => $submission->id,
            'question_id' => $questions[1]->id,
            'choice_id' => $wrongChoices[1]->id,
        ]);

        (new ScoreSubmissionAction())->execute($submission);

        $this->assertSame(1, $submission->fresh()->score);
    }

    /** 未回答（choice_id=null）は不正解扱いになる */
    public function test_未回答は不正解扱いになる(): void
    {
        [$submission, $questions] = $this->setupSubmission(1);

        Answer::factory()->create([
            'submission_id' => $submission->id,
            'question_id' => $questions[0]->id,
            'choice_id' => null,
        ]);

        (new ScoreSubmissionAction())->execute($submission);

        $this->assertSame(0, $submission->fresh()->score);

        $answer = $submission->answers()->first();
        $this->assertFalse($answer->is_correct);
    }
}
