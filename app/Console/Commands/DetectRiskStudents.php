<?php

namespace App\Console\Commands;

use App\Actions\DetectRiskAction;
use App\Models\Cohort;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DetectRiskStudents extends Command
{
    protected $signature = 'risk:detect';

    protected $description = '実施中のコホートに対して要注意者検知を実行する';

    public function handle(DetectRiskAction $action): int
    {
        $today = Carbon::today();

        $cohorts = Cohort::where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today)
            ->get();

        foreach ($cohorts as $cohort) {
            $action->execute($cohort);
            $this->line("detected: {$cohort->name}");
        }

        $this->info("要注意者検知を {$cohorts->count()} 件のコホートで実行しました");

        return self::SUCCESS;
    }
}
