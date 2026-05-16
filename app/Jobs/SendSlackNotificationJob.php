<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Slack Webhook へ非同期送信するジョブ
 * QUEUE_CONNECTION=sync でも動作する
 */
class SendSlackNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly string $webhookUrl,
        private readonly array $payload,
    ) {}

    public function handle(): void
    {
        $response = Http::post($this->webhookUrl, $this->payload);

        if ($response->failed()) {
            Log::warning('Slack通知の送信に失敗しました', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
