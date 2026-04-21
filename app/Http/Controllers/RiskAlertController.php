<?php

namespace App\Http\Controllers;

use App\Enums\RiskAlertReason;
use App\Models\Cohort;
use App\Models\RiskAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RiskAlertController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RiskAlert::class);

        $user = $request->user();

        $query = RiskAlert::with(['user', 'cohort'])
            ->orderByDesc('created_at');

        if ($user->isInstructor()) {
            $cohortIds = $user->instructedCohorts()->pluck('id');
            $query->whereIn('cohort_id', $cohortIds);
        }

        $unresolvedCount = (clone $query)->whereNull('resolved_at')->count();

        if (!$request->input('show_resolved')) {
            $query->whereNull('resolved_at');
        }

        if ($request->filled('reason')) {
            $reason = RiskAlertReason::tryFrom($request->input('reason'));
            if ($reason) {
                $query->where('reason', $reason->value);
            }
        }

        if ($request->filled('cohort_id')) {
            $query->where('cohort_id', $request->input('cohort_id'));
        }

        $alerts = $query->paginate(30)->withQueryString();

        $cohortsQuery = Cohort::orderBy('name');
        if ($user->isInstructor()) {
            $cohortsQuery->whereIn('id', $user->instructedCohorts()->pluck('id'));
        }

        return Inertia::render('RiskAlerts/Index', [
            'alerts' => $alerts,
            'unresolvedCount' => $unresolvedCount,
            'cohorts' => $cohortsQuery->get(['id', 'name']),
            'filters' => $request->only(['show_resolved', 'reason', 'cohort_id']),
        ]);
    }

    public function resolve(Request $request, RiskAlert $alert): RedirectResponse
    {
        $this->authorize('resolve', $alert);

        if ($alert->isResolved()) {
            return back()->withErrors(['error' => 'このアラートは既に解消済みです']);
        }

        $alert->update(['resolved_at' => Carbon::now()]);

        return back()->with('success', 'アラートを解消しました');
    }
}
