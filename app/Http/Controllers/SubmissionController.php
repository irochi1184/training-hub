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
    public function create(Request $request, Test $test): Response
    {
        $this->authorize('create', [Submission::class, $test]);

        $test->load(['questions.choices']);

        $existingSubmission = $test->submissions()
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$existingSubmission) {
            $existingSubmission = Submission::create([
                'test_id' => $test->id,
                'user_id' => $request->user()->id,
                'started_at' => Carbon::now(),
            ]);
        }

        return Inertia::render('Submissions/Create', [
            'test' => $test,
            'submission' => $existingSubmission,
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
