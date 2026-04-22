<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    private const BOM = "\xEF\xBB\xBF";

    public function exportDailyReports(int $curriculumId, ?string $dateFrom, ?string $dateTo): StreamedResponse
    {
        $query = DailyReport::with(['user', 'curriculum'])
            ->where('curriculum_id', $curriculumId)
            ->orderBy('reported_on')
            ->orderBy('user_id');

        if ($dateFrom) {
            $query->where('reported_on', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('reported_on', '<=', $dateTo);
        }

        $reports = $query->get();

        return response()->streamDownload(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, self::BOM);

            fputcsv($handle, ['日付', '受講生名', 'カリキュラム名', '理解度', '学習内容', '感想・気づき']);

            foreach ($reports as $report) {
                fputcsv($handle, [
                    $report->reported_on->format('Y-m-d'),
                    $report->user->name,
                    $report->curriculum->name,
                    $report->understanding_level,
                    $report->content,
                    $report->impression ?? '',
                ]);
            }

            fclose($handle);
        }, 'daily_reports.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportTestResults(int $testId): StreamedResponse
    {
        $test = Test::with([
            'questions',
            'submissions.user',
            'submissions.answers.question',
            'submissions.answers.choice',
        ])->findOrFail($testId);

        return response()->streamDownload(function () use ($test) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, self::BOM);

            $questionHeaders = $test->questions->map(fn ($q) => "Q{$q->position}: {$q->body}")->toArray();
            fputcsv($handle, array_merge(['受講生名', '開始日時', '提出日時', '合計得点'], $questionHeaders));

            foreach ($test->submissions as $submission) {
                $answerMap = $submission->answers->keyBy('question_id');
                $answerValues = $test->questions->map(function ($question) use ($answerMap) {
                    $answer = $answerMap->get($question->id);
                    if (!$answer) {
                        return '未回答';
                    }

                    return $answer->choice?->body ?? '未回答';
                })->toArray();

                fputcsv($handle, array_merge([
                    $submission->user->name,
                    $submission->started_at?->format('Y-m-d H:i:s') ?? '',
                    $submission->submitted_at?->format('Y-m-d H:i:s') ?? '未提出',
                    $submission->score ?? '',
                ], $answerValues));
            }

            fclose($handle);
        }, "test_{$testId}_results.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
