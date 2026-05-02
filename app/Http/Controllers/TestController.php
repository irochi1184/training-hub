<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestRequest;
use App\Http\Requests\UpdateTestRequest;
use App\Models\Curriculum;
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

        $query = Test::with(['curriculum', 'creator'])
            ->withCount('submissions')
            ->orderByDesc('created_at');

        if ($user->isStudent()) {
            $curriculumIds = $user->enrollments()->pluck('curriculum_id');
            $now = now();
            $query->whereIn('curriculum_id', $curriculumIds)
                ->where(fn ($q) => $q
                    ->whereNull('opens_at')
                    ->orWhere('opens_at', '<=', $now)
                )
                ->where(fn ($q) => $q
                    ->whereNull('closes_at')
                    ->orWhere('closes_at', '>=', $now)
                );
        } elseif ($user->isInstructor()) {
            $curriculumIds = $user->instructedCurricula()->pluck('id');
            $query->whereIn('curriculum_id', $curriculumIds);
        }

        $tests = $query->paginate(20);

        return Inertia::render('Tests/Index', ['tests' => $tests]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Test::class);

        $user = $request->user();

        $curricula = $user->isAdmin()
            ? Curriculum::orderBy('name')->get()
            : $user->instructedCurricula()->orderBy('name')->get();

        return Inertia::render('Tests/Create', ['curricula' => $curricula]);
    }

    public function store(StoreTestRequest $request): RedirectResponse
    {
        $this->authorize('create', Test::class);

        $validated = $request->validated();

        $test = Test::create([
            'curriculum_id' => $validated['curriculum_id'],
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        $this->syncQuestions($test, $validated['questions']);

        return redirect()->route('tests.index')->with('success', 'テストを作成しました');
    }

    public function edit(Test $test): Response
    {
        $this->authorize('update', $test);

        $test->load(['questions.choices', 'curriculum']);

        $user = auth()->user();
        $curricula = $user->isAdmin()
            ? Curriculum::orderBy('name')->get()
            : $user->instructedCurricula()->orderBy('name')->get();

        return Inertia::render('Tests/Edit', ['test' => $test, 'curricula' => $curricula]);
    }

    public function update(UpdateTestRequest $request, Test $test): RedirectResponse
    {
        $this->authorize('update', $test);

        $validated = $request->validated();

        $test->update([
            'curriculum_id' => $validated['curriculum_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
        ]);

        $test->questions()->delete();
        $this->syncQuestions($test, $validated['questions']);

        return redirect()->route('tests.index')->with('success', 'テストを更新しました');
    }

    private function syncQuestions(Test $test, array $questions): void
    {
        foreach ($questions as $position => $questionData) {
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
    }

    public function destroy(Test $test): RedirectResponse
    {
        $this->authorize('delete', $test);

        $test->delete();

        return redirect()->route('tests.index')->with('success', 'テストを削除しました');
    }
}
