<?php

namespace App\Notifications;

use App\Models\Announcement;

/**
 * お知らせ投稿通知のペイロード定義
 * SlackNotificationService が Webhook へ送信する
 */
class AnnouncementPostedNotification
{
    public function __construct(
        private readonly Announcement $announcement,
    ) {}

    /** Slack メッセージ用のペイロードを返す */
    public function toSlackPayload(): array
    {
        $title = $this->announcement->title;
        $poster = $this->announcement->creator->name;

        return [
            'text' => sprintf(
                'お知らせが投稿されました。タイトル: %s / 投稿者: %s',
                $title,
                $poster,
            ),
        ];
    }
}
