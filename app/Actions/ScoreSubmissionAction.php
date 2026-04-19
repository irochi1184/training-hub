<?php

namespace App\Actions;

use App\Models\Submission;
use Illuminate\Support\Carbon;

class ScoreSubmissionAction
{
    public function execute(Submission $submission): void
    {
        $submission->load(['answers.choice', 'test.questions']);

        $totalScore = 0;

        foreach ($submission->answers as $answer) {
            $question = $submission->test->questions->find($answer->question_id);

            if (!$question) {
                continue;
            }

            $isCorrect = $answer->choice_id !== null && $answer->choice?->is_correct === true;

            $answer->update(['is_correct' => $isCorrect]);

            if ($isCorrect) {
                $totalScore += $question->score;
            }
        }

        $submission->update([
            'score' => $totalScore,
            'submitted_at' => Carbon::now(),
        ]);
    }
}
