<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Cohort;
use App\Models\DailyReport;
use App\Models\RiskAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $data = match ($user->role) {
            UserRole::Admin => $this->adminData(),
            UserRole::Instructor => $this->instructorData($user),
            UserRole::Student => $this->studentData($user),
        };

        return Inertia::render('Dashboard', $data);
    }

    private function adminData(): array
    {
        return [
            'unresolvedAlertCount' => RiskAlert::whereNull('resolved_at')->count(),
            'recentReports' => DailyReport::with(['user', 'cohort'])
                ->orderByDesc('reported_on')
                ->limit(10)
                ->get(),
        ];
    }

    private function instructorData(\App\Models\User $user): array
    {
        $cohortIds = $user->instructedCohorts()->pluck('id');

        return [
            'unresolvedAlertCount' => RiskAlert::whereIn('cohort_id', $cohortIds)
                ->whereNull('resolved_at')
                ->count(),
            'recentReports' => DailyReport::with(['user', 'cohort'])
                ->whereIn('cohort_id', $cohortIds)
                ->orderByDesc('reported_on')
                ->limit(10)
                ->get(),
        ];
    }

    private function studentData(\App\Models\User $user): array
    {
        return [
            'recentReports' => $user->dailyReports()
                ->with('cohort')
                ->orderByDesc('reported_on')
                ->limit(7)
                ->get(),
            'submissions' => $user->submissions()
                ->with('test')
                ->whereNotNull('submitted_at')
                ->orderByDesc('submitted_at')
                ->limit(5)
                ->get(),
        ];
    }
}
