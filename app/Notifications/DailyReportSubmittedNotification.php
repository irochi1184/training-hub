<?php

namespace App\Notifications;

use App\Models\DailyReport;

/**
 * 日報提出通知のペイロード定義
 * SlackNotificationService が Webhook へ送信する
 */
class DailyReportSubmittedNotification
{
    public function __construct(
        private readonly DailyReport $report,
    ) {}

    /** Slack メッセージ用のペイロードを返す */
    public function toSlackPayload(): array
    {
        $student = $this->report->user->name;
        $date = $this->report->reported_on->format('Y/m/d');
        $level = $this->report->understanding_level;

        return [
            'text' => sprintf(
                '日報が提出されました。受講生: %s / 日付: %s / 理解度: %d',
                $student,
                $date,
                $level,
            ),
        ];
    }
}
