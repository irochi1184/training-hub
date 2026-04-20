<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestRequest;
use App\Http\Requests\UpdateTestRequest;
use App\Models\Cohort;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Test::class);

        $user = $request->user();

        $query = Test::with(['cohort', 'creator'])
            ->withCount('submissions')
            ->orderByDesc('created_at');

        if ($user->isStudent()) {
            $cohortIds = $user->enrollments()->pluck('cohort_id');
            $now = now();
            $query->whereIn('cohort_id', $cohortIds)
                ->where(fn ($q) => $q
                    ->whereNull('opens_at')
                    ->orWhere('opens_at', '<=', $now)
                )
                ->where(fn ($q) => $q
                    ->whereNull('closes_at')
                    ->orWhere('closes_at', '>=', $now)
                );
        } elseif ($user->isInstructor()) {
            $cohortIds = $user->instructedCohorts()->pluck('id');
            $query->whereIn('cohort_id', $cohortIds);
        }

        $tests = $query->paginate(20);

        return Inertia::render('Tests/Index', ['tests' => $tests]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Test::class);

        $user = $request->user();

        $cohorts = $user->isAdmin()
            ? Cohort::with('course')->orderBy('name')->get()
            : $user->instructedCohorts()->with('course')->orderBy('name')->get();

        return Inertia::render('Tests/Create', ['cohorts' => $cohorts]);
    }

    public function store(StoreTestRequest $request): RedirectResponse
    {
        $this->authorize('create', Test::class);

        $validated = $request->validated();

        $test = Test::create([
            'cohort_id' => $validated['cohort_id'],
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        foreach ($validated['questions'] as $position => $questionData) {
            $question = $test->questions()->create([
                'body' => $questionData['body'],
                'position' => $position + 1,
                'score' => $questionData['score'],
            ]);

            foreach ($questionData['choices'] as $choicePosition => $choiceData) {
                $question->choices()->create([
                    'body' => $choiceData['body'],
                    'is_correct' => $choiceData['is_correct'],
                    'position' => $choicePosition + 1,
                ]);
            }
        }

        return redirect()->route('tests.index')->with('success', 'テストを作成しました');
    }

    public function edit(Test $test): Response
    {
        $this->authorize('update', $test);

        $test->load(['questions.choices', 'cohort']);

        $user = auth()->user();
        $cohorts = $user->isAdmin()
            ? Cohort::with('course')->orderBy('name')->get()
            : $user->instructedCohorts()->with('course')->orderBy('name')->get();

        return Inertia::render('Tests/Edit', ['test' => $test, 'cohorts' => $cohorts]);
    }

    public function update(UpdateTestRequest $request, Test $test): RedirectResponse
    {
        $this->authorize('update', $test);

        $validated = $request->validated();

        $test->update([
            'cohort_id' => $validated['cohort_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        $test->questions()->delete();

        foreach ($validated['questions'] as $position => $questionData) {
            $question = $test->questions()->create([
                'body' => $questionData['body'],
                'position' => $position + 1,
                'score' => $questionData['score'],
            ]);

            foreach ($questionData['choices'] as $choicePosition => $choiceData) {
                $question->choices()->create([
                    'body' => $choiceData['body'],
                    'is_correct' => $choiceData['is_correct'],
                    'position' => $choicePosition + 1,
                ]);
            }
        }

        return redirect()->route('tests.index')->with('success', 'テストを更新しました');
    }

    public function destroy(Test $test): RedirectResponse
    {
        $this->authorize('delete', $test);

        $test->delete();

        return redirect()->route('tests.index')->with('success', 'テストを削除しました');
    }
}
