<?php

namespace App\Console\Commands;

use App\Actions\DetectRiskAction;
use App\Models\Cohort;
use App\Models\RiskAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DetectRiskStudents extends Command
{
    protected $signature = 'risk:detect';

    protected $description = '要注意受講生を検知してアラートを生成する';

    public function handle(DetectRiskAction $action): int
    {
        $cohorts = Cohort::query()
            ->whereDate('starts_on', '<=', Carbon::today())
            ->whereDate('ends_on', '>=', Carbon::today())
            ->with(['enrollments.user'])
            ->get();

        if ($cohorts->isEmpty()) {
            $this->info('対象コホートが存在しません。');
            return self::SUCCESS;
        }

        $this->info(sprintf('対象コホート: %d件', $cohorts->count()));

        $totalNewAlerts = 0;

        foreach ($cohorts as $cohort) {
            $beforeCount = RiskAlert::where('cohort_id', $cohort->id)
                ->whereNull('resolved_at')
                ->count();

            $action->execute($cohort);

            $afterCount = RiskAlert::where('cohort_id', $cohort->id)
                ->whereNull('resolved_at')
                ->count();

            $newAlerts = $afterCount - $beforeCount;
            $totalNewAlerts += $newAlerts;

            $this->line(sprintf(
                '  [%s] 新規アラート: %d件',
                $cohort->name,
                $newAlerts,
            ));
        }

        $this->info(sprintf('検知完了 - 新規アラート合計: %d件', $totalNewAlerts));

        return self::SUCCESS;
    }
}
