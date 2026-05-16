<?php

namespace App\Http\Controllers;

use App\Enums\NotificationEventType;
use App\Models\NotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingController extends Controller
{
    /** 通知設定一覧を表示 */
    public function index(Request $request): Response
    {
        $organization = $request->user()->organization;

        // 既存設定をイベント種別をキーにして取得
        $existingSettings = NotificationSetting::where('organization_id', $organization->id)
            ->get()
            ->keyBy(fn (NotificationSetting $s) => $s->event_type->value);

        // 全イベント種別について設定を組み立てる
        $settings = collect(NotificationEventType::cases())->map(function (NotificationEventType $type) use ($existingSettings) {
            $setting = $existingSettings->get($type->value);
            return [
                'event_type' => $type->value,
                'enabled'    => $setting?->enabled ?? false,
                'channel'    => $setting?->channel ?? '',
            ];
        })->all();

        return Inertia::render('Settings/Notifications', [
            'webhookUrl' => $organization->slack_webhook_url ?? '',
            'settings'   => $settings,
        ]);
    }

    /** 通知設定を一括更新 */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'webhook_url'            => ['nullable', 'url', 'max:500'],
            'settings'               => ['required', 'array'],
            'settings.*.event_type'  => ['required', 'string'],
            'settings.*.enabled'     => ['required', 'boolean'],
            'settings.*.channel'     => ['nullable', 'string', 'max:100'],
        ]);

        $organization = $request->user()->organization;

        // Webhook URL を組織に保存
        $organization->update(['slack_webhook_url' => $validated['webhook_url'] ?? null]);

        // 各イベントの設定をupsert
        foreach ($validated['settings'] as $item) {
            NotificationSetting::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'event_type'      => $item['event_type'],
                ],
                [
                    'enabled' => $item['enabled'],
                    'channel' => $item['channel'] ?? null,
                ],
            );
        }

        return redirect()->route('notification-settings.index')
            ->with('success', '通知設定を保存しました。');
    }

    /** Webhook URLにテストメッセージを送信 */
    public function testWebhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'webhook_url' => ['required', 'url', 'max:500'],
        ]);

        try {
            $response = Http::timeout(10)->post($validated['webhook_url'], [
                'text' => '✅ Training Hub からのテスト送信です。Slack通知設定が正常に機能しています。',
            ]);

            if ($response->successful()) {
                return response()->json(['message' => 'テスト送信に成功しました。']);
            }

            return response()->json(['message' => 'Slackへの送信に失敗しました。Webhook URLを確認してください。'], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => '送信中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }
}
