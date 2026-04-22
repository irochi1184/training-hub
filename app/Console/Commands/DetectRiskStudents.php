<?php

namespace App\Console\Commands;

use App\Actions\DetectRiskAction;
use App\Models\Curriculum;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DetectRiskStudents extends Command
{
    protected $signature = 'risk:detect';

    protected $description = '実施中のカリキュラムに対して要注意者検知を実行する';

    public function handle(DetectRiskAction $action): int
    {
        $today = Carbon::today();

        $curricula = Curriculum::where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today)
            ->get();

        foreach ($curricula as $curriculum) {
            $action->execute($curriculum);
            $this->line("detected: {$curriculum->name}");
        }

        $this->info("要注意者検知を {$curricula->count()} 件のカリキュラムで実行しました");

        return self::SUCCESS;
    }
}
