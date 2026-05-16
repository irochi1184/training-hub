<?php

namespace App\Console\Commands;

use App\Models\Curriculum;
use App\Models\User;
use App\Services\AiSummaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateWeeklySummaries extends Command
{
    protected $signature = 'summaries:generate-weekly {--week= : 対象週の月曜日 (Y-m-d)}';

    protected $description = '週次AI要約を生成する';

    public function handle(AiSummaryService $service): int
    {
        $weekOption = $this->option('week');

        if ($weekOption) {
            $weekStart = Carbon::parse($weekOption)->startOfWeek(Carbon::MONDAY);
        } else {
            // デフォルトは前週の月曜日
            $weekStart = Carbon::today()->subWeek()->startOfWeek(Carbon::MONDAY);
        }

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $this->info("対象週: {$weekStart->toDateString()} 〜 {$weekEnd->toDateString()}");

        $generatedCount = 0;

        // 全受講生の週次サマリーを生成
        $students = User::whereHas('organization')
            ->where('role', 'student')
            ->with('organization:id,name')
            ->get();

        foreach ($students as $student) {
            $summary = $service->generateWeeklyStudentSummary($student, $weekStart);
            if ($summary !== null) {
                $generatedCount++;
                $this->line("受講生サマリー生成: {$student->name}");
            }
        }

        // 全カリキュラムのクラスサマリーを生成
        $curricula = Curriculum::with('organization:id,name')->get();

        foreach ($curricula as $curriculum) {
            $summary = $service->generateWeeklyClassSummary($curriculum, $weekStart);
            if ($summary !== null) {
                $generatedCount++;
                $this->line("クラスサマリー生成: {$curriculum->name}");
            }
        }

        $this->info("週次AI要約を {$generatedCount} 件生成しました");

        return self::SUCCESS;
    }
}
