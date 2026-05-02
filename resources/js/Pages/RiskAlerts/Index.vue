<template>
  <AppLayout>
    <div class="max-w-6xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">要注意者一覧</h1>
        <p class="text-sm text-slate-500">{{ alerts.total }} 件</p>
      </div>

      <!-- フィルター -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-5 mb-5 space-y-3">
        <div class="flex items-center gap-4 flex-wrap">
          <label class="text-sm font-medium text-slate-700">表示対象:</label>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium rounded transition-colors"
              :class="!showResolved
                ? 'bg-red-600 text-white'
                : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'"
              @click="applyFilter({ show_resolved: false })"
            >
              未解消のみ
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-sm font-medium rounded transition-colors"
              :class="showResolved
                ? 'bg-slate-700 text-white'
                : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'"
              @click="applyFilter({ show_resolved: true })"
            >
              全件
            </button>
          </div>
        </div>
        <div class="flex items-center gap-4 flex-wrap">
          <label class="text-sm font-medium text-slate-700 shrink-0">絞り込み:</label>
          <select
            :value="filters.reason ?? ''"
            class="text-sm border border-slate-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilter({ reason: ($event.target as HTMLSelectElement).value || undefined })"
          >
            <option value="">すべての理由</option>
            <option value="report_missing">日報未提出</option>
            <option value="low_understanding">理解度低下</option>
            <option value="low_score">得点率低下</option>
          </select>
          <select
            :value="filters.curriculum_id ?? ''"
            class="text-sm border border-slate-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilter({ curriculum_id: ($event.target as HTMLSelectElement).value || undefined })"
          >
            <option value="">すべてのカリキュラム</option>
            <option v-for="curriculum in curricula" :key="curriculum.id" :value="curriculum.id">
              {{ curriculum.name }}
            </option>
          </select>
          <button
            v-if="hasActiveFilter"
            type="button"
            class="text-sm text-slate-500 hover:text-slate-700 underline"
            @click="clearFilters"
          >
            絞り込み解除
          </button>
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
        :empty="alerts.data.length === 0"
        :empty-message="showResolved ? 'アラートがありません' : '未解消のアラートはありません'"
        :col-span="6"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">受講生</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">カリキュラム</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">理由</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">詳細</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">発生日</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">状態</th>
        </template>

        <template #body>
          <tr
            v-for="alert in alerts.data"
            :key="alert.id"
            class="hover:bg-slate-50 transition-colors"
            :class="alert.resolved_at ? 'opacity-60' : ''"
          >
            <td class="px-4 py-3">
              <Link
                :href="`/students/${alert.user_id}`"
                class="text-sm font-medium text-indigo-600 hover:underline"
              >
                {{ alert.user?.name ?? '—' }}
              </Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ alert.curriculum?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <ReasonBadge :reason="alert.reason" />
            </td>
            <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate">{{ alert.detail ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(alert.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span
                  v-if="alert.resolved_at"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-700"
                >
                  解消済
                </span>
                <template v-else>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                    未解消
                  </span>
                  <button
                    type="button"
                    class="text-xs text-slate-500 border border-slate-300 rounded px-2 py-0.5 hover:bg-slate-100 transition-colors"
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

      <Pagination :data="alerts" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import type { RiskAlert, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import ReasonBadge from '@/Components/ReasonBadge.vue';
import { formatDate } from '@/utils/formatDate';

type CurriculumOption = { id: number; name: string };

const props = defineProps<{
  alerts: PaginatedData<RiskAlert>;
  unresolvedCount: number;
  curricula: CurriculumOption[];
  filters: {
    show_resolved?: string;
    reason?: string;
    curriculum_id?: string;
  };
}>();

const showResolved = computed(() => props.filters.show_resolved === '1');

const hasActiveFilter = computed(
  () => Boolean(props.filters.reason) || Boolean(props.filters.curriculum_id),
);

type FilterPatch = {
  show_resolved?: boolean;
  reason?: string | undefined;
  curriculum_id?: string | undefined;
};

function applyFilter(patch: FilterPatch): void {
  const query: Record<string, string> = {};

  const nextShowResolved = patch.show_resolved ?? showResolved.value;
  if (nextShowResolved) query.show_resolved = '1';

  const nextReason = 'reason' in patch ? patch.reason : props.filters.reason;
  if (nextReason) query.reason = nextReason;

  const nextCurriculumId = 'curriculum_id' in patch ? patch.curriculum_id : props.filters.curriculum_id;
  if (nextCurriculumId) query.curriculum_id = String(nextCurriculumId);

  router.get('/risk-alerts', query, { preserveState: true, replace: true });
}

function clearFilters(): void {
  router.get(
    '/risk-alerts',
    showResolved.value ? { show_resolved: '1' } : {},
    { preserveState: true, replace: true },
  );
}

function resolve(alertId: number): void {
  router.patch(`/risk-alerts/${alertId}/resolve`, {}, { preserveScroll: true });
}

</script>
