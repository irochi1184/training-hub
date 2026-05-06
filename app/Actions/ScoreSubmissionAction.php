<?php

namespace App\Actions;

use App\Models\Submission;
use Illuminate\Support\Carbon;

class ScoreSubmissionAction
{
    public function execute(Submission $submission): void
    {
        $submission->load(['answers.choice', 'test.questions.choices']);

        $totalScore = 0;

        // 問題ごとにグループ化して採点
        $answersByQuestion = $submission->answers->groupBy('question_id');

        foreach ($submission->test->questions as $question) {
            $questionAnswers = $answersByQuestion->get($question->id, collect());

            if ($question->question_type === 'multiple') {
                // 複数選択: 正解の選択肢IDセットと回答の選択肢IDセットが完全一致で得点
                $correctChoiceIds = $question->choices
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->sort()
                    ->values()
                    ->all();

                $selectedChoiceIds = $questionAnswers
                    ->pluck('choice_id')
                    ->filter()
                    ->sort()
                    ->values()
                    ->all();

                $isCorrect = $correctChoiceIds === $selectedChoiceIds;

                foreach ($questionAnswers as $answer) {
                    $answer->update(['is_correct' => $isCorrect]);
                }

                if ($isCorrect) {
                    $totalScore += $question->score;
                }
            } else {
                // 単一選択: 従来通り
                $answer = $questionAnswers->first();
                if (!$answer) {
                    continue;
                }

                $isCorrect = $answer->choice_id !== null && $answer->choice?->is_correct === true;
                $answer->update(['is_correct' => $isCorrect]);

                if ($isCorrect) {
                    $totalScore += $question->score;
                }
            }
        }

        $submission->update([
            'score' => $totalScore,
            'submitted_at' => Carbon::now(),
        ]);
    }
}
