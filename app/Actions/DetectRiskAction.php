<?php

namespace App\Actions;

use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DetectRiskAction
{
    private const REPORT_MISSING_DAYS = 3;
    private const LOW_UNDERSTANDING_THRESHOLD = 2.0;
    private const LOW_SCORE_THRESHOLD = 50.0;

    /**
     * 要注意者を検知し、新規作成されたアラートのコレクションを返す
     *
     * @return Collection<int, RiskAlert>
     */
    public function execute(Curriculum $curriculum): Collection
    {
        $students = $curriculum->enrollments()->with('user')->get()->pluck('user');
        $newAlerts = collect();

        foreach ($students as $student) {
            $alert = $this->detectReportMissing($student, $curriculum);
            if ($alert !== null) {
                $newAlerts->push($alert);
            }

            $alert = $this->detectLowUnderstanding($student, $curriculum);
            if ($alert !== null) {
                $newAlerts->push($alert);
            }

            $alert = $this->detectLowScore($student, $curriculum);
            if ($alert !== null) {
                $newAlerts->push($alert);
            }
        }

        return $newAlerts;
    }

    private function detectReportMissing(User $student, Curriculum $curriculum): ?RiskAlert
    {
        $since = Carbon::today()->subDays(self::REPORT_MISSING_DAYS);
        $reportCount = $student->dailyReports()
            ->where('curriculum_id', $curriculum->id)
            ->where('reported_on', '>=', $since)
            ->count();

        if ($reportCount === 0) {
            return $this->createAlertIfNotExists(
                $student,
                $curriculum,
                RiskAlertReason::ReportMissing,
                sprintf('%d日以上日報が提出されていません', self::REPORT_MISSING_DAYS),
            );
        }

        return null;
    }

    private function detectLowUnderstanding(User $student, Curriculum $curriculum): ?RiskAlert
    {
        $average = $student->dailyReports()
            ->where('curriculum_id', $curriculum->id)
            ->avg('understanding_level');

        if ($average !== null && $average <= self::LOW_UNDERSTANDING_THRESHOLD) {
            return $this->createAlertIfNotExists(
                $student,
                $curriculum,
                RiskAlertReason::LowUnderstanding,
                sprintf('理解度平均: %.1f', $average),
            );
        }

        return null;
    }

    private function detectLowScore(User $student, Curriculum $curriculum): ?RiskAlert
    {
        $submissions = $student->submissions()
            ->whereHas('test', fn ($q) => $q->where('curriculum_id', $curriculum->id))
            ->whereNotNull('score')
            ->with('test.questions')
            ->get();

        if ($submissions->isEmpty()) {
            return null;
        }

        $totalMaxScore = 0;
        $totalScore = 0;

        foreach ($submissions as $submission) {
            $maxScore = $submission->test->questions->sum('score');
            $totalMaxScore += $maxScore;
            $totalScore += $submission->score;
        }

        if ($totalMaxScore === 0) {
            return null;
        }

        $percentage = ($totalScore / $totalMaxScore) * 100;

        if ($percentage <= self::LOW_SCORE_THRESHOLD) {
            return $this->createAlertIfNotExists(
                $student,
                $curriculum,
                RiskAlertReason::LowScore,
                sprintf('テスト平均得点率: %.1f%%', $percentage),
            );
        }

        return null;
    }

    /** 未解決のアラートが存在しなければ作成して返す。既存なら null を返す */
    private function createAlertIfNotExists(
        User $student,
        Curriculum $curriculum,
        RiskAlertReason $reason,
        string $detail,
    ): ?RiskAlert {
        $exists = RiskAlert::where('user_id', $student->id)
            ->where('curriculum_id', $curriculum->id)
            ->where('reason', $reason->value)
            ->whereNull('resolved_at')
            ->exists();

        if ($exists) {
            return null;
        }

        $alert = RiskAlert::create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reason' => $reason->value,
            'detail' => $detail,
        ]);

        $alert->setRelation('user', $student);

        return $alert;
    }
}
