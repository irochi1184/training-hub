<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyReportRequest;
use App\Models\Cohort;
use App\Models\DailyReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DailyReport::class);

        $user = $request->user();

        $query = DailyReport::with(['user', 'cohort', 'comments.user'])
            ->orderByDesc('reported_on');

        if ($user->isInstructor()) {
            $cohortIds = $user->instructedCohorts()->pluck('id');
            $query->whereIn('cohort_id', $cohortIds);
        }

        $reports = $query->paginate(30);

        $cohortsQuery = Cohort::orderBy('name');
        if ($user->isInstructor()) {
            $cohortsQuery->whereIn('id', $user->instructedCohorts()->pluck('id'));
        }
        $cohorts = $cohortsQuery->get();

        return Inertia::render('DailyReports/Index', [
            'reports' => $reports,
            'cohorts' => $cohorts,
            'filters' => $request->only(['cohort_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', DailyReport::class);

        $user = $request->user();
        $cohorts = $user->enrollments()->with('cohort')->get()->pluck('cohort');

        return Inertia::render('DailyReports/Create', [
            'cohorts' => $cohorts,
            'today'   => now()->format('Y-m-d'),
        ]);
    }

    public function store(StoreDailyReportRequest $request): RedirectResponse
    {
        $this->authorize('create', DailyReport::class);

        $report = DailyReport::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('daily-reports.show', $report)
            ->with('success', '日報を提出しました');
    }

    public function show(Request $request, DailyReport $report): Response
    {
        $this->authorize('view', $report);

        $report->load(['user', 'cohort', 'comments.user']);

        return Inertia::render('DailyReports/Show', ['report' => $report]);
    }
}
