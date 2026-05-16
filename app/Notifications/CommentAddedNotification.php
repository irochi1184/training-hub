<?php

namespace App\Notifications;

use App\Models\DailyReport;
use App\Models\User;

/**
 * 講師コメント追加通知のペイロード定義
 * SlackNotificationService が Webhook へ送信する
 */
class CommentAddedNotification
{
    public function __construct(
        private readonly DailyReport $report,
        private readonly User $instructor,
    ) {}

    /** Slack メッセージ用のペイロードを返す */
    public function toSlackPayload(): array
    {
        $instructorName = $this->instructor->name;
        $date = $this->report->reported_on->format('Y/m/d');

        return [
            'text' => sprintf(
                '講師コメントが追加されました。講師: %s / 対象日報: %s',
                $instructorName,
                $date,
            ),
        ];
    }
}
