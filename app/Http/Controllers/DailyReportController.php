<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyReportRequest;
use App\Models\Curriculum;
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

        $query = DailyReport::with(['user', 'curriculum', 'comments.user'])
            ->orderByDesc('reported_on');

        $curriculumIds = $user->isInstructor()
            ? $user->instructedCurricula()->pluck('curricula.id')
            : null;

        if ($curriculumIds !== null) {
            $query->whereIn('curriculum_id', $curriculumIds);
        }

        if ($request->filled('curriculum_id')) {
            $query->where('curriculum_id', $request->integer('curriculum_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('reported_on', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('reported_on', '<=', $request->string('date_to'));
        }

        $reports = $query->paginate(30)->withQueryString();

        $curriculaQuery = Curriculum::orderBy('name');
        if ($curriculumIds !== null) {
            $curriculaQuery->whereIn('id', $curriculumIds);
        }
        $curricula = $curriculaQuery->get();

        return Inertia::render('DailyReports/Index', [
            'reports' => $reports,
            'curricula' => $curricula,
            'filters' => $request->only(['curriculum_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', DailyReport::class);

        $user = $request->user();
        $curricula = $user->enrollments()->with('curriculum')->get()->pluck('curriculum');

        return Inertia::render('DailyReports/Create', [
            'curricula' => $curricula,
            'today'   => now()->format('Y-m-d'),
        ]);
    }

    public function store(StoreDailyReportRequest $request): RedirectResponse
    {
        $this->authorize('create', DailyReport::class);

        $validated = $request->validated();

        $existing = DailyReport::where('user_id', $request->user()->id)
            ->where('curriculum_id', $validated['curriculum_id'])
            ->whereDate('reported_on', $validated['reported_on'])
            ->first();

        if ($existing) {
            $existing->update($validated);
            return redirect()->route('daily-reports.show', $existing)
                ->with('success', '日報を更新しました');
        }

        $report = DailyReport::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('daily-reports.show', $report)
            ->with('success', '日報を提出しました');
    }

    public function show(Request $request, DailyReport $report): Response
    {
        $this->authorize('view', $report);

        $report->load(['user', 'curriculum', 'comments.user']);

        return Inertia::render('DailyReports/Show', ['report' => $report]);
    }
}
