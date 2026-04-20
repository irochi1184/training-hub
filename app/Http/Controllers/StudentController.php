<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Cohort;
use App\Models\DailyReport;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $user = $request->user();

        $query = User::with(['latestEnrollment.cohort'])
            ->where('role', UserRole::Student->value)
            ->addSelect(['*',
                'latest_understanding_level' => DailyReport::select('understanding_level')
                    ->whereColumn('user_id', 'users.id')
                    ->orderByDesc('reported_on')
                    ->limit(1),
                'has_unresolved_alert' => RiskAlert::selectRaw('COUNT(*) > 0')
                    ->whereColumn('user_id', 'users.id')
                    ->whereNull('resolved_at')
                    ->limit(1),
            ]);

        if ($user->isInstructor()) {
            $cohortIds = $user->instructedCohorts()->pluck('id');
            $query->whereHas('enrollments', fn ($q) => $q->whereIn('cohort_id', $cohortIds));
        }

        if ($request->filled('cohort_id')) {
            $query->whereHas('enrollments', fn ($q) => $q->where('cohort_id', $request->input('cohort_id')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $students = $query->orderBy('name')->paginate(30)->withQueryString();

        $cohortsQuery = Cohort::orderBy('name');
        if ($user->isInstructor()) {
            $cohortsQuery->whereIn('id', $user->instructedCohorts()->pluck('id'));
        }

        return Inertia::render('Students/Index', [
            'students' => $students,
            'cohorts' => $cohortsQuery->get(),
            'filters' => $request->only(['cohort_id', 'search']),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);

        $user->load([
            'enrollments.cohort.course',
            'dailyReports' => fn ($q) => $q->with('comments')->orderByDesc('reported_on')->limit(30),
            'submissions' => fn ($q) => $q->with('test')->whereNotNull('submitted_at')->orderByDesc('submitted_at'),
            'riskAlerts' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        // 理解度推移: 直近30件を日付昇順で返す
        $understandingTrend = $user->dailyReports
            ->sortBy('reported_on')
            ->values()
            ->map(fn ($r) => [
                'date'  => $r->reported_on,
                'level' => $r->understanding_level,
            ]);

        // テスト結果サマリー
        $scores = $user->submissions
            ->whereNotNull('score')
            ->pluck('score');

        $testSummary = [
            'count'   => $user->submissions->count(),
            'average' => $scores->isNotEmpty() ? round($scores->avg(), 1) : null,
            'max'     => $scores->isNotEmpty() ? $scores->max() : null,
            'min'     => $scores->isNotEmpty() ? $scores->min() : null,
        ];

        return Inertia::render('Students/Show', [
            'student'            => $user,
            'enrollments'        => $user->enrollments,
            'dailyReports'       => $user->dailyReports->sortByDesc('reported_on')->values(),
            'submissions'        => $user->submissions,
            'riskAlerts'         => $user->riskAlerts,
            'understandingTrend' => $understandingTrend,
            'testSummary'        => $testSummary,
        ]);
    }
}
