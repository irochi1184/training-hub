<?php

namespace App\Notifications;

use App\Models\RiskAlert;

/**
 * 要注意者検知通知のペイロード定義
 * SlackNotificationService が Webhook へ送信する
 */
class RiskDetectedNotification
{
    public function __construct(
        private readonly RiskAlert $alert,
    ) {}

    /** Slack メッセージ用のペイロードを返す */
    public function toSlackPayload(): array
    {
        $student = $this->alert->user->name;
        $detail = $this->alert->detail;

        return [
            'text' => sprintf(
                '要注意者が検知されました。受講生: %s / 理由: %s',
                $student,
                $detail,
            ),
        ];
    }
}
