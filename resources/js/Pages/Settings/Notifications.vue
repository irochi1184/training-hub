<template>
  <AppLayout>
    <div class="max-w-3xl">
      <h1 class="text-2xl font-bold text-slate-900 mb-6">Slack通知設定</h1>

      <form @submit.prevent="save">
        <!-- Webhook URL セクション -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
          <h2 class="text-base font-semibold text-slate-800 mb-1">Webhook URL</h2>
          <p class="text-sm text-slate-500 mb-4">
            Slack の Incoming Webhook URL を設定してください。設定後、各イベントの通知が有効になります。
          </p>

          <div class="space-y-3">
            <div>
              <label for="webhook_url" class="block text-sm font-medium text-slate-700 mb-1">
                Webhook URL
              </label>
              <input
                id="webhook_url"
                v-model="form.webhook_url"
                type="url"
                placeholder="https://hooks.slack.com/services/..."
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                :class="{ 'border-red-400': form.errors.webhook_url }"
              />
              <p v-if="form.errors.webhook_url" class="mt-1 text-xs text-red-600">
                {{ form.errors.webhook_url }}
              </p>
            </div>

            <!-- テスト送信ボタン（URL入力済みの場合のみ表示） -->
            <div v-if="form.webhook_url">
              <button
                type="button"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded hover:bg-slate-200 transition-colors disabled:opacity-50"
                :disabled="testSending"
                @click="sendTest"
              >
                <svg
                  v-if="testSending"
                  class="w-4 h-4 animate-spin"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                テスト送信
              </button>

              <!-- テスト送信結果 -->
              <p
                v-if="testResult"
                class="mt-2 text-xs font-medium"
                :class="testResult.ok ? 'text-emerald-600' : 'text-red-600'"
              >
                {{ testResult.message }}
              </p>
            </div>
          </div>
        </div>

        <!-- 通知イベント一覧 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden mb-6">
          <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-800">通知イベント</h2>
            <p class="text-sm text-slate-500 mt-0.5">
              有効にしたイベントが発生したとき、Slackに通知を送信します。
            </p>
          </div>

          <table class="w-full text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-48">
                  イベント
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-24">
                  有効
                </th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                  チャンネル（省略可）
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(setting, index) in form.settings"
                :key="setting.event_type"
                class="hover:bg-slate-50/50 transition-colors"
              >
                <td class="px-6 py-4">
                  <span class="font-medium text-slate-800">
                    {{ EVENT_LABELS[setting.event_type] ?? setting.event_type }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <!-- トグルスイッチ -->
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="setting.enabled"
                    class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                    :class="setting.enabled ? 'bg-indigo-600' : 'bg-slate-200'"
                    @click="form.settings[index].enabled = !form.settings[index].enabled"
                  >
                    <span
                      class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200 mt-0.5"
                      :class="setting.enabled ? 'translate-x-5 ml-0.5' : 'translate-x-0.5'"
                    />
                  </button>
                </td>
                <td class="px-6 py-4">
                  <input
                    v-model="form.settings[index].channel"
                    type="text"
                    placeholder="#general"
                    class="block w-full max-w-xs rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400"
                    :disabled="!setting.enabled"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Webhook URLが未設定の場合の注意 -->
        <div
          v-if="!form.webhook_url"
          class="flex items-start gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg mb-6 text-sm text-amber-800"
        >
          <svg class="w-5 h-5 shrink-0 mt-0.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
          </svg>
          <span>Webhook URL が設定されていないため、通知は送信されません。</span>
        </div>

        <!-- 保存ボタン -->
        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50"
            :disabled="form.processing"
          >
            <svg
              v-if="form.processing"
              class="w-4 h-4 animate-spin"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            設定を保存
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { NotificationSettingItem } from '@/types';

const props = defineProps<{
  webhookUrl: string;
  settings: NotificationSettingItem[];
}>();

// イベント種別の日本語ラベル
const EVENT_LABELS: Record<string, string> = {
  daily_report_submitted: '日報提出',
  comment_added:          'コメント追加',
  risk_detected:          '要注意者検知',
  test_completed:         'テスト完了',
  announcement_posted:    'お知らせ投稿',
};

const form = useForm({
  webhook_url: props.webhookUrl,
  settings: props.settings.map((s) => ({ ...s })),
});

// テスト送信の状態
const testSending = ref(false);
const testResult = ref<{ ok: boolean; message: string } | null>(null);

function save(): void {
  form.put('/settings/notifications');
}

async function sendTest(): Promise<void> {
  if (!form.webhook_url) return;

  testSending.value = true;
  testResult.value = null;

  try {
    const res = await axios.post('/settings/notifications/test', {
      webhook_url: form.webhook_url,
    });
    testResult.value = { ok: true, message: res.data.message };
  } catch (err: unknown) {
    const message =
      axios.isAxiosError(err) && err.response?.data?.message
        ? err.response.data.message
        : '送信中にエラーが発生しました。';
    testResult.value = { ok: false, message };
  } finally {
    testSending.value = false;
  }
}
</script>
