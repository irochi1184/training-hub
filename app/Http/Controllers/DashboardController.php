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

        $totalStudents = \App\Models\User::where('role', UserRole::Student)->count();
        $todayReports = DailyReport::where('reported_on', Carbon::today())->distinct('user_id')->count('user_id');
        $todayReportRate = $totalStudents > 0 ? round(($todayReports / $totalStudents) * 100) : 0;

        $enrollmentCountByCurriculum = Enrollment::selectRaw('curriculum_id, COUNT(*) as count')
            ->groupBy('curriculum_id')
            ->pluck('count', 'curriculum_id');

        $testCountByCurriculum = TestModel::selectRaw('curriculum_id, COUNT(*) as count')
            ->groupBy('curriculum_id')
            ->pluck('count', 'curriculum_id');

        $totalSubmissionsExpected = 0;
        foreach ($testCountByCurriculum as $curriculumId => $testCount) {
            $totalSubmissionsExpected += $testCount * $enrollmentCountByCurriculum->get($curriculumId, 0);
        }

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
        $curriculumIds = $user->instructedCurricula()->pluck('id');

        $riskAlertCount = RiskAlert::whereIn('curriculum_id', $curriculumIds)
            ->whereNull('resolved_at')
            ->count();

        $todayReportCount = DailyReport::whereIn('curriculum_id', $curriculumIds)
            ->where('reported_on', Carbon::today())
            ->count();

        $recentTest = TestModel::whereIn('curriculum_id', $curriculumIds)->latest()->first();
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
            ->with('curriculum')
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
