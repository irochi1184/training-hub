<?php

namespace App\Http\Controllers;

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

        $alerts = $query->get();

        return Inertia::render('RiskAlerts/Index', ['alerts' => $alerts]);
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
