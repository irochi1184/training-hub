<?php

namespace App\Services;

use App\Enums\NotificationEventType;
use App\Jobs\SendSlackNotificationJob;
use App\Models\Organization;

class SlackNotificationService
{
    /**
     * 組織の通知設定を確認し、有効であれば Slack Webhook へ非同期送信する
     *
     * @param array<string, mixed> $payload toSlackPayload() の戻り値
     */
    public function send(Organization $org, NotificationEventType $eventType, array $payload): void
    {
        // Webhook URL 未設定なら何もしない
        if (!$org->isSlackEnabled()) {
            return;
        }

        // 通知設定を確認（設定レコードが存在しない場合は送信しない）
        $setting = $org->notificationSettings()
            ->where('event_type', $eventType->value)
            ->first();

        if ($setting === null || !$setting->enabled) {
            return;
        }

        // チャンネル指定があればペイロードに付加
        if (filled($setting->channel)) {
            $payload['channel'] = $setting->channel;
        }

        SendSlackNotificationJob::dispatch($org->slack_webhook_url, $payload);
    }
}
