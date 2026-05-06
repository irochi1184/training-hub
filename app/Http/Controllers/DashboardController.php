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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

        $curricula = Curriculum::query()->orderBy('starts_on')->get();

        return [
            'adminStats' => [
                'risk_alert_count' => $riskAlertCount,
                'today_report_rate' => $todayReportRate,
                'test_completion_rate' => $testCompletionRate,
            ],
            'recentRiskAlerts' => $this->recentRiskAlerts(),
            'curriculumSummaries' => $this->curriculumSummaries($curricula),
            'understandingDistribution' => $this->understandingDistribution($curricula),
            'reportRateTrend' => $this->reportRateTrend($totalStudents),
            'curriculumScoreComparison' => $this->curriculumScoreComparison($curricula),
        ];
    }

    private function instructorData(User $user): array
    {
        $curriculumIds = $user->instructedCurricula()->pluck('curricula.id');

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

        $curricula = Curriculum::query()
            ->whereIn('id', $curriculumIds)
            ->orderBy('starts_on')
            ->get();

        $totalStudentsInCurricula = Enrollment::whereIn('curriculum_id', $curriculumIds)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'instructorStats' => [
                'risk_alert_count' => $riskAlertCount,
                'today_report_count' => $todayReportCount,
                'recent_test_avg' => $recentTestAvg,
            ],
            'recentRiskAlerts' => $this->recentRiskAlerts($curriculumIds->all()),
            'curriculumSummaries' => $this->curriculumSummaries($curricula),
            'understandingDistribution' => $this->understandingDistribution($curricula),
            'reportRateTrend' => $this->reportRateTrend($totalStudentsInCurricula, $curriculumIds->all()),
            'curriculumScoreComparison' => $this->curriculumScoreComparison($curricula),
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
     * @param  EloquentCollection<int, Curriculum>  $curricula
     * @return array<int, array<string, mixed>>
     */
    private function curriculumSummaries(EloquentCollection $curricula): array
    {
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
     * カリキュラム別に直近7日間の理解度レベル（1〜5）ごとの件数を集計する。
     *
     * @param  EloquentCollection<int, Curriculum>  $curricula
     * @return array<int, array{curriculum_name: string, levels: array<int, int>}>
     */
    private function understandingDistribution(EloquentCollection $curricula): array
    {
        if ($curricula->isEmpty()) {
            return [];
        }

        $since = Carbon::today()->subDays(self::UNDERSTANDING_TREND_DAYS - 1)->toDateString();
        $curriculumIds = $curricula->pluck('id')->all();

        // curriculum_id と understanding_level の組み合わせごとに件数を取得
        $rows = DailyReport::selectRaw('curriculum_id, understanding_level, COUNT(*) as count')
            ->whereIn('curriculum_id', $curriculumIds)
            ->where('reported_on', '>=', $since)
            ->groupBy('curriculum_id', 'understanding_level')
            ->get();

        // [curriculum_id => [level => count]] の形に変換
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row->curriculum_id][$row->understanding_level] = (int) $row->count;
        }

        return $curricula->map(function (Curriculum $curriculum) use ($indexed): array {
            $levelCounts = $indexed[$curriculum->id] ?? [];
            // levels[0] がレベル1の件数、levels[4] がレベル5の件数
            $levels = array_map(
                fn (int $level) => $levelCounts[$level] ?? 0,
                [1, 2, 3, 4, 5]
            );

            return [
                'curriculum_name' => $curriculum->name,
                'levels' => $levels,
            ];
        })->all();
    }

    /**
     * 直近7日間の日別日報提出率を算出する。
     * admin の場合は全受講生、instructor の場合は担当カリキュラムの受講生を母数とする。
     *
     * @param  int  $totalStudents  母数となる受講生数
     * @param  array<int>|null  $curriculumIds  instructor の場合のカリキュラム絞り込み
     * @return array<int, array{date: string, rate: int}>
     */
    private function reportRateTrend(int $totalStudents, ?array $curriculumIds = null): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays(self::UNDERSTANDING_TREND_DAYS - 1);

        $query = DailyReport::selectRaw('reported_on, COUNT(DISTINCT user_id) as count')
            ->whereBetween('reported_on', [$start->toDateString(), $end->toDateString()])
            ->groupBy('reported_on');

        if ($curriculumIds !== null) {
            $query->whereIn('curriculum_id', $curriculumIds);
        }

        // [date_string => count] の形に変換
        $countsByDate = $query->get()
            ->keyBy(fn (DailyReport $report) => $report->reported_on->toDateString())
            ->map(fn (DailyReport $report) => (int) $report->count);

        $trend = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $date = $day->toDateString();
            $count = $countsByDate[$date] ?? 0;
            $rate = $totalStudents > 0 ? (int) round(($count / $totalStudents) * 100) : 0;

            $trend[] = [
                'date' => $date,
                'rate' => $rate,
            ];
        }

        return $trend;
    }

    /**
     * カリキュラム別の全テスト平均点を集計する。
     *
     * @param  EloquentCollection<int, Curriculum>  $curricula
     * @return array<int, array{curriculum_name: string, avg_score: float|null}>
     */
    private function curriculumScoreComparison(EloquentCollection $curricula): array
    {
        if ($curricula->isEmpty()) {
            return [];
        }

        $curriculumIds = $curricula->pluck('id')->all();

        $avgScores = Submission::selectRaw('tests.curriculum_id, AVG(submissions.score) as avg')
            ->join('tests', 'submissions.test_id', '=', 'tests.id')
            ->whereIn('tests.curriculum_id', $curriculumIds)
            ->whereNotNull('submissions.score')
            ->groupBy('tests.curriculum_id')
            ->pluck('avg', 'curriculum_id');

        return $curricula->map(function (Curriculum $curriculum) use ($avgScores): array {
            $avg = $avgScores[$curriculum->id] ?? null;

            return [
                'curriculum_name' => $curriculum->name,
                'avg_score' => $avg !== null ? round((float) $avg, 1) : null,
            ];
        })->all();
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
