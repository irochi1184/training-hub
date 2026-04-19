<?php

namespace App\Actions;

use App\Enums\RiskAlertReason;
use App\Models\Cohort;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Support\Carbon;

class DetectRiskAction
{
    private const REPORT_MISSING_DAYS = 3;
    private const LOW_UNDERSTANDING_THRESHOLD = 2.0;
    private const LOW_SCORE_THRESHOLD = 50.0;

    public function execute(Cohort $cohort): void
    {
        $students = $cohort->enrollments()->with('user')->get()->pluck('user');

        foreach ($students as $student) {
            $this->detectReportMissing($student, $cohort);
            $this->detectLowUnderstanding($student, $cohort);
            $this->detectLowScore($student, $cohort);
        }
    }

    private function detectReportMissing(User $student, Cohort $cohort): void
    {
        $since = Carbon::today()->subDays(self::REPORT_MISSING_DAYS);
        $reportCount = $student->dailyReports()
            ->where('cohort_id', $cohort->id)
            ->where('reported_on', '>=', $since)
            ->count();

        if ($reportCount === 0) {
            $this->createAlertIfNotExists(
                $student,
                $cohort,
                RiskAlertReason::ReportMissing,
                sprintf('%d日以上日報が提出されていません', self::REPORT_MISSING_DAYS),
            );
        }
    }

    private function detectLowUnderstanding(User $student, Cohort $cohort): void
    {
        $average = $student->dailyReports()
            ->where('cohort_id', $cohort->id)
            ->avg('understanding_level');

        if ($average !== null && $average <= self::LOW_UNDERSTANDING_THRESHOLD) {
            $this->createAlertIfNotExists(
                $student,
                $cohort,
                RiskAlertReason::LowUnderstanding,
                sprintf('理解度平均: %.1f', $average),
            );
        }
    }

    private function detectLowScore(User $student, Cohort $cohort): void
    {
        $submissions = $student->submissions()
            ->whereHas('test', fn ($q) => $q->where('cohort_id', $cohort->id))
            ->whereNotNull('score')
            ->with('test.questions')
            ->get();

        if ($submissions->isEmpty()) {
            return;
        }

        $totalMaxScore = 0;
        $totalScore = 0;

        foreach ($submissions as $submission) {
            $maxScore = $submission->test->questions->sum('score');
            $totalMaxScore += $maxScore;
            $totalScore += $submission->score;
        }

        if ($totalMaxScore === 0) {
            return;
        }

        $percentage = ($totalScore / $totalMaxScore) * 100;

        if ($percentage <= self::LOW_SCORE_THRESHOLD) {
            $this->createAlertIfNotExists(
                $student,
                $cohort,
                RiskAlertReason::LowScore,
                sprintf('テスト平均得点率: %.1f%%', $percentage),
            );
        }
    }

    private function createAlertIfNotExists(
        User $student,
        Cohort $cohort,
        RiskAlertReason $reason,
        string $detail,
    ): void {
        $exists = RiskAlert::where('user_id', $student->id)
            ->where('cohort_id', $cohort->id)
            ->where('reason', $reason->value)
            ->whereNull('resolved_at')
            ->exists();

        if (!$exists) {
            RiskAlert::create([
                'user_id' => $student->id,
                'cohort_id' => $cohort->id,
                'reason' => $reason->value,
                'detail' => $detail,
            ]);
        }
    }
}
