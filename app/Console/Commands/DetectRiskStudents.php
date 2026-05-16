<?php

namespace App\Console\Commands;

use App\Actions\DetectRiskAction;
use App\Enums\NotificationEventType;
use App\Models\Curriculum;
use App\Notifications\RiskDetectedNotification;
use App\Services\SlackNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DetectRiskStudents extends Command
{
    protected $signature = 'risk:detect';

    protected $description = '実施中のカリキュラムに対して要注意者検知を実行する';

    public function handle(DetectRiskAction $action, SlackNotificationService $slack): int
    {
        $today = Carbon::today();

        $curricula = Curriculum::where('starts_on', '<=', $today)
            ->where('ends_on', '>=', $today)
            ->with('organization')
            ->get();

        foreach ($curricula as $curriculum) {
            $newAlerts = $action->execute($curriculum);
            $this->line("detected: {$curriculum->name}");

            // 新規アラートごとに通知送信
            foreach ($newAlerts as $alert) {
                $slack->send(
                    $curriculum->organization,
                    NotificationEventType::RiskDetected,
                    (new RiskDetectedNotification($alert))->toSlackPayload(),
                );
            }
        }

        $this->info("要注意者検知を {$curricula->count()} 件のカリキュラムで実行しました");

        return self::SUCCESS;
    }
}
