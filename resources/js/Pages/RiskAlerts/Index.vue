<template>
  <AppLayout>
    <div class="max-w-6xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">要注意者一覧</h1>
        <p class="text-sm text-gray-500">{{ filteredAlerts.length }} 件</p>
      </div>

      <!-- フィルター -->
      <div class="bg-white rounded-lg border border-gray-200 p-4 mb-5">
        <div class="flex items-center gap-4">
          <label class="text-sm font-medium text-gray-700">表示対象:</label>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium rounded transition-colors"
              :class="showResolved === false
                ? 'bg-red-600 text-white'
                : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
              @click="showResolved = false"
            >
              未解消のみ
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium rounded transition-colors"
              :class="showResolved === true
                ? 'bg-gray-700 text-white'
                : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50'"
              @click="showResolved = true"
            >
              全件
            </button>
          </div>
        </div>
      </div>

      <!-- 未解消件数の警告 -->
      <div
        v-if="unresolvedCount > 0"
        class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center gap-3"
      >
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
        </svg>
        <p class="text-sm font-medium text-red-800">
          未解消のアラートが <span class="text-lg font-bold">{{ unresolvedCount }}</span> 件あります
        </p>
      </div>

      <!-- テーブル -->
      <DataTable
        :empty="filteredAlerts.length === 0"
        :empty-message="showResolved ? 'アラートがありません' : '未解消のアラートはありません'"
        :col-span="6"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">受講生</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">コホート</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">理由</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">詳細</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">発生日</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">状態</th>
        </template>

        <template #body>
          <tr
            v-for="alert in filteredAlerts"
            :key="alert.id"
            class="hover:bg-gray-50 transition-colors"
            :class="alert.resolved_at ? 'opacity-60' : ''"
          >
            <td class="px-4 py-3">
              <Link
                :href="`/students/${alert.user_id}`"
                class="text-sm font-medium text-blue-600 hover:underline"
              >
                {{ alert.user?.name ?? '—' }}
              </Link>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ alert.cohort?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <ReasonBadge :reason="alert.reason" />
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ alert.detail ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(alert.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span
                  v-if="alert.resolved_at"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700"
                >
                  解消済
                </span>
                <template v-else>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                    未解消
                  </span>
                  <button
                    type="button"
                    class="text-xs text-gray-500 border border-gray-300 rounded px-2 py-0.5 hover:bg-gray-100 transition-colors"
                    @click="resolve(alert.id)"
                  >
                    解消にする
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </template>
      </DataTable>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import type { RiskAlert } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import ReasonBadge from '@/Components/ReasonBadge.vue';

const props = defineProps<{
  alerts: RiskAlert[];
}>();

// フィルター状態
const showResolved = ref(false);

const filteredAlerts = computed(() => {
  if (showResolved.value) return props.alerts;
  return props.alerts.filter((a) => a.resolved_at === null);
});

const unresolvedCount = computed(() =>
  props.alerts.filter((a) => a.resolved_at === null).length,
);

function resolve(alertId: number): void {
  router.patch(`/risk-alerts/${alertId}/resolve`, {}, { preserveScroll: true });
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  });
}
</script>
