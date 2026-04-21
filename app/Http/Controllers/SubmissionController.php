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

        // 既に提出済みの場合は結果ページへリダイレクト
        $existingSubmission = $test->submissions()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingSubmission && $existingSubmission->isSubmitted()) {
            return redirect()->route('submissions.show', $existingSubmission);
        }

        // 受験中（未提出）の場合はそのまま継続
        if ($existingSubmission) {
            return Inertia::render('Tests/Take', [
                'test' => $test,
                'submission' => $existingSubmission,
            ]);
        }

        // 新規受験: ポリシーチェック
        $this->authorize('create', [Submission::class, $test]);

        $submission = Submission::create([
            'test_id' => $test->id,
            'user_id' => $request->user()->id,
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
            ->firstOrFail();

        if ($submission->isSubmitted()) {
            return redirect()->route('submissions.show', $submission);
        }

        foreach ($request->validated('answers') as $answerData) {
            $submission->answers()->updateOrCreate(
                ['question_id' => $answerData['question_id']],
                ['choice_id' => $answerData['choice_id'] ?? null],
            );
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

        return Inertia::render('Submissions/Show', ['submission' => $submission]);
    }
}
