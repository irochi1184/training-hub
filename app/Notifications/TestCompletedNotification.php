<?php

namespace App\Notifications;

use App\Models\Submission;

/**
 * テスト完了通知のペイロード定義
 * SlackNotificationService が Webhook へ送信する
 */
class TestCompletedNotification
{
    public function __construct(
        private readonly Submission $submission,
    ) {}

    /** Slack メッセージ用のペイロードを返す */
    public function toSlackPayload(): array
    {
        $student = $this->submission->user->name;
        $testName = $this->submission->test->title;
        $score = $this->submission->score ?? 0;

        return [
            'text' => sprintf(
                'テストが完了しました。受講生: %s / テスト: %s / スコア: %d点',
                $student,
                $testName,
                $score,
            ),
        ];
    }
}
