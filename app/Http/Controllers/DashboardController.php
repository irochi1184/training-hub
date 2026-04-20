<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test as TestModel;
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

        return Inertia::render('Dashboard/Index', $data);
    }

    private function adminData(): array
    {
        $riskAlertCount = RiskAlert::whereNull('resolved_at')->count();

        // 本日の日報提出率
        $totalStudents = \App\Models\User::where('role', UserRole::Student)->count();
        $todayReports = DailyReport::where('reported_on', Carbon::today())->distinct('user_id')->count('user_id');
        $todayReportRate = $totalStudents > 0 ? round(($todayReports / $totalStudents) * 100) : 0;

        // テスト受験完了率（N+1を避けるため事前集計）
        $enrollmentCountByCohort = Enrollment::selectRaw('cohort_id, COUNT(*) as count')
            ->groupBy('cohort_id')
            ->pluck('count', 'cohort_id');

        $totalSubmissionsExpected = TestModel::all()->sum(
            fn ($t) => $enrollmentCountByCohort->get($t->cohort_id, 0)
        );
        $completedSubmissions = Submission::whereNotNull('submitted_at')->count();
        $testCompletionRate = $totalSubmissionsExpected > 0
            ? round(($completedSubmissions / $totalSubmissionsExpected) * 100)
            : 0;

        return [
            'adminStats' => [
                'risk_alert_count' => $riskAlertCount,
                'today_report_rate' => $todayReportRate,
                'test_completion_rate' => $testCompletionRate,
            ],
        ];
    }

    private function instructorData(\App\Models\User $user): array
    {
        $cohortIds = $user->instructedCohorts()->pluck('id');

        $riskAlertCount = RiskAlert::whereIn('cohort_id', $cohortIds)
            ->whereNull('resolved_at')
            ->count();

        $todayReportCount = DailyReport::whereIn('cohort_id', $cohortIds)
            ->where('reported_on', Carbon::today())
            ->count();

        // 直近テスト平均点
        $recentTest = TestModel::whereIn('cohort_id', $cohortIds)->latest()->first();
        $recentTestAvg = null;
        if ($recentTest) {
            $avg = Submission::where('test_id', $recentTest->id)
                ->whereNotNull('score')
                ->avg('score');
            $recentTestAvg = $avg !== null ? round($avg, 1) : null;
        }

        return [
            'instructorStats' => [
                'risk_alert_count' => $riskAlertCount,
                'today_report_count' => $todayReportCount,
                'recent_test_avg' => $recentTestAvg,
            ],
        ];
    }

    private function studentData(\App\Models\User $user): array
    {
        $hasMissingReport = !$user->dailyReports()
            ->where('reported_on', Carbon::today())
            ->exists();

        $latestReport = $user->dailyReports()
            ->with('cohort')
            ->orderByDesc('reported_on')
            ->first();

        $latestSubmission = $user->submissions()
            ->with('test')
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();

        return [
            'studentStats' => [
                'has_missing_report' => $hasMissingReport,
                'latest_report' => $latestReport,
                'latest_submission' => $latestSubmission,
            ],
        ];
    }
}
