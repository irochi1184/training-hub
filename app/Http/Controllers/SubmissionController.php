<?php

namespace App\Http\Controllers;

use App\Actions\ScoreSubmissionAction;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Submission;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function create(Request $request, Test $test): Response|RedirectResponse
    {
        $test->load(['questions.choices']);
        $userId = $request->user()->id;

        // 受験中（未提出）の提出があればそのまま継続
        $inProgress = $test->submissions()
            ->where('user_id', $userId)
            ->whereNull('submitted_at')
            ->first();

        if ($inProgress) {
            return Inertia::render('Tests/Take', [
                'test' => $test,
                'submission' => $inProgress,
            ]);
        }

        // 再受験不可（max_attempts=null）で提出済みがあれば最新結果へリダイレクト
        $latestSubmitted = $test->submissions()
            ->where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->latest('attempt')
            ->first();

        if ($latestSubmitted && !$test->allowsRetake()) {
            return redirect()->route('submissions.show', $latestSubmitted);
        }

        // 新規受験: ポリシーチェック（残り回数確認含む）
        $this->authorize('create', [Submission::class, $test]);

        $nextAttempt = $latestSubmitted ? $latestSubmitted->attempt + 1 : 1;

        $submission = Submission::create([
            'test_id' => $test->id,
            'user_id' => $userId,
            'attempt' => $nextAttempt,
            'started_at' => Carbon::now(),
        ]);

        return Inertia::render('Tests/Take', [
            'test' => $test,
            'submission' => $submission,
        ]);
    }

    public function store(StoreSubmissionRequest $request, ScoreSubmissionAction $scoreAction, Test $test): RedirectResponse
    {
        $submission = Submission::where('test_id', $test->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('submitted_at')
            ->latest('attempt')
            ->firstOrFail();

        if ($submission->isSubmitted()) {
            return redirect()->route('submissions.show', $submission);
        }

        // 既存回答を削除して再作成（複数選択対応）
        $submission->answers()->delete();

        foreach ($request->validated('answers') as $answerData) {
            $choiceIds = $answerData['choice_ids'] ?? [];

            if (!empty($choiceIds)) {
                // 複数選択問題
                foreach ($choiceIds as $choiceId) {
                    $submission->answers()->create([
                        'question_id' => $answerData['question_id'],
                        'choice_id' => $choiceId,
                    ]);
                }
            } else {
                // 単一選択問題
                $submission->answers()->create([
                    'question_id' => $answerData['question_id'],
                    'choice_id' => $answerData['choice_id'] ?? null,
                ]);
            }
        }

        $scoreAction->execute($submission);

        return redirect()->route('submissions.show', $submission)
            ->with('success', '回答を提出しました');
    }

    public function show(Request $request, Submission $submission): Response
    {
        $this->authorize('view', $submission);

        $submission->load([
            'test.questions.choices',
            'answers.choice',
            'user',
        ]);

        // 再受験対応: 同一テスト・同一ユーザーの全受験履歴
        $allAttempts = Submission::where('test_id', $submission->test_id)
            ->where('user_id', $submission->user_id)
            ->whereNotNull('submitted_at')
            ->orderBy('attempt')
            ->get(['id', 'attempt', 'score', 'submitted_at']);

        $bestScore = $allAttempts->max('score');

        return Inertia::render('Submissions/Show', [
            'submission' => $submission,
            'allAttempts' => $allAttempts,
            'bestScore' => $bestScore,
        ]);
    }
}
