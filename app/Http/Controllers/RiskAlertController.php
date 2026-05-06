<?php

namespace App\Http\Controllers;

use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
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

        $query = RiskAlert::with(['user', 'curriculum'])
            ->orderByDesc('created_at');

        $curriculumIds = $user->isInstructor()
            ? $user->instructedCurricula()->pluck('id')
            : null;

        if ($curriculumIds !== null) {
            $query->whereIn('curriculum_id', $curriculumIds);
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

        if ($request->filled('curriculum_id')) {
            $query->where('curriculum_id', $request->input('curriculum_id'));
        }

        $alerts = $query->paginate(30)->withQueryString();

        $curriculaQuery = Curriculum::orderBy('name');
        if ($curriculumIds !== null) {
            $curriculaQuery->whereIn('id', $curriculumIds);
        }

        return Inertia::render('RiskAlerts/Index', [
            'alerts' => $alerts,
            'unresolvedCount' => $unresolvedCount,
            'curricula' => $curriculaQuery->get(['id', 'name']),
            'filters' => $request->only(['show_resolved', 'reason', 'curriculum_id']),
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
