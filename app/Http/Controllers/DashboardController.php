<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test as TestModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const RECENT_RISK_ALERT_LIMIT = 5;
    private const UNDERSTANDING_TREND_DAYS = 7;

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

        $totalStudents = User::where('role', UserRole::Student)->count();
        $todayReports = DailyReport::where('reported_on', Carbon::today())->distinct('user_id')->count('user_id');
        $todayReportRate = $totalStudents > 0 ? (int) round(($todayReports / $totalStudents) * 100) : 0;

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
            ? (int) round(($completedSubmissions / $totalSubmissionsExpected) * 100)
            : 0;

        return [
            'adminStats' => [
                'risk_alert_count' => $riskAlertCount,
                'today_report_rate' => $todayReportRate,
                'test_completion_rate' => $testCompletionRate,
            ],
            'recentRiskAlerts' => $this->recentRiskAlerts(),
            'curriculumSummaries' => $this->curriculumSummaries(Curriculum::query()),
        ];
    }

    private function instructorData(User $user): array
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
            'recentRiskAlerts' => $this->recentRiskAlerts($curriculumIds->all()),
            'curriculumSummaries' => $this->curriculumSummaries(
                Curriculum::query()->whereIn('id', $curriculumIds)
            ),
        ];
    }

    private function studentData(User $user): array
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
            'understandingTrend' => $this->understandingTrend($user),
        ];
    }

    /**
     * @param  array<int>|null  $curriculumIds
     * @return array<int, array<string, mixed>>
     */
    private function recentRiskAlerts(?array $curriculumIds = null): array
    {
        $query = RiskAlert::query()
            ->with(['user:id,name', 'curriculum:id,name'])
            ->whereNull('resolved_at')
            ->latest()
            ->limit(self::RECENT_RISK_ALERT_LIMIT);

        if ($curriculumIds !== null) {
            $query->whereIn('curriculum_id', $curriculumIds);
        }

        return $query->get()->map(fn (RiskAlert $alert) => [
            'id' => $alert->id,
            'reason' => $alert->reason,
            'detail' => $alert->detail,
            'created_at' => $alert->created_at?->toDateString(),
            'user_name' => $alert->user?->name,
            'curriculum_name' => $alert->curriculum?->name,
        ])->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Curriculum>  $query
     * @return array<int, array<string, mixed>>
     */
    private function curriculumSummaries($query): array
    {
        $curricula = $query->orderBy('starts_on')->get();

        if ($curricula->isEmpty()) {
            return [];
        }

        $curriculumIds = $curricula->pluck('id')->all();

        $enrollmentCounts = Enrollment::selectRaw('curriculum_id, COUNT(*) as count')
            ->whereIn('curriculum_id', $curriculumIds)
            ->groupBy('curriculum_id')
            ->pluck('count', 'curriculum_id');

        $avgUnderstanding = DailyReport::selectRaw('curriculum_id, AVG(understanding_level) as avg')
            ->whereIn('curriculum_id', $curriculumIds)
            ->groupBy('curriculum_id')
            ->pluck('avg', 'curriculum_id');

        $avgScore = Submission::selectRaw('tests.curriculum_id, AVG(submissions.score) as avg')
            ->join('tests', 'submissions.test_id', '=', 'tests.id')
            ->whereIn('tests.curriculum_id', $curriculumIds)
            ->whereNotNull('submissions.score')
            ->groupBy('tests.curriculum_id')
            ->pluck('avg', 'curriculum_id');

        $unresolvedAlerts = RiskAlert::selectRaw('curriculum_id, COUNT(*) as count')
            ->whereIn('curriculum_id', $curriculumIds)
            ->whereNull('resolved_at')
            ->groupBy('curriculum_id')
            ->pluck('count', 'curriculum_id');

        return $curricula->map(fn (Curriculum $curriculum) => [
            'id' => $curriculum->id,
            'name' => $curriculum->name,
            'enrollment_count' => (int) ($enrollmentCounts[$curriculum->id] ?? 0),
            'avg_understanding' => isset($avgUnderstanding[$curriculum->id])
                ? round((float) $avgUnderstanding[$curriculum->id], 1)
                : null,
            'avg_score' => isset($avgScore[$curriculum->id])
                ? round((float) $avgScore[$curriculum->id], 1)
                : null,
            'unresolved_alert_count' => (int) ($unresolvedAlerts[$curriculum->id] ?? 0),
        ])->all();
    }

    /**
     * @return array<int, array{date: string, level: int|null}>
     */
    private function understandingTrend(User $user): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(self::UNDERSTANDING_TREND_DAYS - 1);

        $reports = DailyReport::where('user_id', $user->id)
            ->whereBetween('reported_on', [$start->toDateString(), $end->toDateString()])
            ->get(['reported_on', 'understanding_level'])
            ->keyBy(fn (DailyReport $report) => $report->reported_on->toDateString());

        $trend = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $trend[] = [
                'date' => $date,
                'level' => isset($reports[$date]) ? (int) $reports[$date]->understanding_level : null,
            ];
        }

        return $trend;
    }
}
