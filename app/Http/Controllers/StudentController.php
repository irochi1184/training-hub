<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Curriculum;
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

        $query = User::with(['latestEnrollment.curriculum'])
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
            $curriculumIds = $user->instructedCurricula()->pluck('id');
            $query->whereHas('enrollments', fn ($q) => $q->whereIn('curriculum_id', $curriculumIds));
        }

        if ($request->filled('curriculum_id')) {
            $query->whereHas('enrollments', fn ($q) => $q->where('curriculum_id', $request->input('curriculum_id')));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $students = $query->orderBy('name')->paginate(30)->withQueryString();

        $curriculaQuery = Curriculum::orderBy('name');
        if ($user->isInstructor()) {
            $curriculaQuery->whereIn('id', $user->instructedCurricula()->pluck('id'));
        }

        return Inertia::render('Students/Index', [
            'students' => $students,
            'curricula' => $curriculaQuery->get(),
            'filters' => $request->only(['curriculum_id', 'search']),
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);

        $user->load([
            'enrollments.curriculum',
            'dailyReports' => fn ($q) => $q->with('comments')->orderByDesc('reported_on')->limit(30),
            'submissions' => fn ($q) => $q->with('test')->whereNotNull('submitted_at')->orderByDesc('submitted_at'),
            'riskAlerts' => fn ($q) => $q->orderByDesc('created_at'),
        ]);

        $understandingTrend = $user->dailyReports
            ->sortBy('reported_on')
            ->values()
            ->map(fn ($r) => [
                'date'  => $r->reported_on,
                'level' => $r->understanding_level,
            ]);

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
