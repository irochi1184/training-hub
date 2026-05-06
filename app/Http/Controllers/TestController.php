<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestRequest;
use App\Http\Requests\UpdateTestRequest;
use App\Models\Answer;
use App\Models\Curriculum;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function show(Request $request, Test $test): Response
    {
        $this->authorize('view', $test);

        // テスト本体（questions.choices を position 順で eager load）
        $test->load([
            'curriculum',
            'questions.choices' => fn ($q) => $q->orderBy('position'),
            'questions' => fn ($q) => $q->orderBy('position'),
        ]);

        $questionIds = $test->questions->pluck('id');

        // 問題別・選択肢別の回答集計を 1 クエリで取得（N+1 回避）
        $answerStats = Answer::query()
            ->select([
                'answers.question_id',
                'answers.choice_id',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_count'),
            ])
            ->whereIn('answers.question_id', $questionIds)
            ->whereNotNull('answers.choice_id')
            ->join('submissions', 'answers.submission_id', '=', 'submissions.id')
            ->whereNotNull('submissions.submitted_at')
            ->groupBy('answers.question_id', 'answers.choice_id')
            ->get();

        // 問題 ID → 回答統計のマップ（total_answers はサブミットされた回答数）
        $statsByQuestion = $answerStats->groupBy('question_id');

        $questionAnalytics = $test->questions->map(function ($question) use ($statsByQuestion) {
            $questionStats = $statsByQuestion->get($question->id, collect());

            $totalAnswers = $questionStats->sum('total_count');
            $correctCount = $questionStats->sum('correct_count');
            $correctRate = $totalAnswers > 0 ? (int) round($correctCount / $totalAnswers * 100) : 0;

            // 選択肢別の集計マップ（choice_id → 統計）
            $choiceStatMap = $questionStats->keyBy('choice_id');

            $choiceStats = $question->choices->map(function ($choice) use ($choiceStatMap, $totalAnswers) {
                $stat = $choiceStatMap->get($choice->id);
                $count = $stat ? (int) $stat->total_count : 0;
                $rate = $totalAnswers > 0 ? (int) round($count / $totalAnswers * 100) : 0;

                return [
                    'choice_id' => $choice->id,
                    'body' => $choice->body,
                    'is_correct' => $choice->is_correct,
                    'count' => $count,
                    'rate' => $rate,
                ];
            })->values()->all();

            return [
                'question_id' => $question->id,
                'position' => $question->position,
                'body' => $question->body,
                'score' => $question->score,
                'total_answers' => $totalAnswers,
                'correct_count' => $correctCount,
                'correct_rate' => $correctRate,
                'choice_stats' => $choiceStats,
            ];
        })->values()->all();

        // 受験者一覧（score 降順、N+1 回避のため user を eager load）
        $submissions = $test->submissions()
            ->with('user:id,name')
            ->whereNotNull('submitted_at')
            ->orderByDesc('score')
            ->orderBy('submitted_at')
            ->get()
            ->map(fn ($s) => [
                'submission_id' => $s->id,
                'user_name' => $s->user->name,
                'score' => $s->score,
                'submitted_at' => $s->submitted_at,
            ])
            ->values()
            ->all();

        // テスト全体サマリー
        $totalPoints = $test->questions->sum('score');
        $scoreStats = $test->submissions()
            ->whereNotNull('submitted_at')
            ->selectRaw('COUNT(*) as total_submissions, AVG(score) as avg_score, MAX(score) as max_score, MIN(score) as min_score')
            ->first();

        $summary = [
            'total_submissions' => (int) ($scoreStats->total_submissions ?? 0),
            'avg_score' => $scoreStats->total_submissions > 0 ? round((float) $scoreStats->avg_score, 1) : null,
            'max_score' => $scoreStats->total_submissions > 0 ? (int) $scoreStats->max_score : null,
            'min_score' => $scoreStats->total_submissions > 0 ? (int) $scoreStats->min_score : null,
            'total_points' => $totalPoints,
        ];

        return Inertia::render('Tests/Analytics', [
            'test' => $test,
            'questionAnalytics' => $questionAnalytics,
            'submissions' => $submissions,
            'summary' => $summary,
        ]);
    }

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
            $curriculumIds = $user->instructedCurricula()->pluck('curricula.id');
            $query->whereIn('curriculum_id', $curriculumIds);
        }

        $tests = $query->paginate(20);

        // 学生向け: 各テストの受験回数・最高点・残り回数を付与
        if ($user->isStudent()) {
            $tests->getCollection()->transform(function ($test) use ($user) {
                $submissions = $test->submissions()
                    ->where('user_id', $user->id)
                    ->whereNotNull('submitted_at')
                    ->get(['score', 'attempt']);

                $test->my_attempts = $submissions->count();
                $test->my_best_score = $submissions->max('score');
                $test->remaining_attempts = $test->remainingAttempts($user->id);

                return $test;
            });
        }

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
            'max_attempts' => $validated['max_attempts'] ?? null,
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
            'max_attempts' => $validated['max_attempts'] ?? null,
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
                'question_type' => $questionData['question_type'] ?? 'single',
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
