<template>
  <AppLayout>
    <div class="max-w-6xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">日報一覧</h1>
        <p class="text-sm text-slate-500">全 {{ reports.total }} 件</p>
      </div>

      <!-- フィルター -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-5 mb-5">
        <form @submit.prevent="applyFilter" class="flex items-end gap-4 flex-wrap">
          <!-- コホート -->
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">コホート</label>
            <select
              v-model="filterForm.cohort_id"
              class="block w-44 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            >
              <option value="">すべて</option>
              <option v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                {{ cohort.name }}
              </option>
            </select>
          </div>

          <!-- 日付（From） -->
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">日付（開始）</label>
            <input
              v-model="filterForm.date_from"
              type="date"
              class="block w-40 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <!-- 日付（To） -->
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">日付（終了）</label>
            <input
              v-model="filterForm.date_to"
              type="date"
              class="block w-40 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <div class="flex gap-2">
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
            >
              絞り込み
            </button>
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              @click="clearFilter"
            >
              クリア
            </button>
          </div>
        </form>
      </div>

      <!-- テーブル -->
      <DataTable
        :empty="reports.data.length === 0"
        empty-message="条件に一致する日報がありません"
        :col-span="5"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">日付</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">受講生</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">コホート</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">理解度</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">コメント</th>
        </template>
        <template #body>
          <tr
            v-for="report in reports.data"
            :key="report.id"
            class="hover:bg-slate-50 transition-colors cursor-pointer"
            @click="goToReport(report.id)"
          >
            <td class="px-4 py-3 text-sm text-slate-900 font-medium">{{ formatDate(report.reported_on) }}</td>
            <td class="px-4 py-3 text-sm text-slate-800">{{ report.user?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ report.cohort?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <UnderstandingBadge :level="report.understanding_level" />
            </td>
            <td class="px-4 py-3 text-sm text-slate-500">{{ report.comments?.length ?? 0 }} 件</td>
          </tr>
        </template>
      </DataTable>

      <Pagination :data="reports" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import type { DailyReport, Cohort, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import UnderstandingBadge from '@/Components/UnderstandingBadge.vue';
import { formatDate } from '@/utils/formatDate';

const props = defineProps<{
  reports: PaginatedData<DailyReport>;
  cohorts: Cohort[];
  filters: {
    cohort_id?: string;
    date_from?: string;
    date_to?: string;
  };
}>();

const filterForm = reactive({
  cohort_id: props.filters.cohort_id ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
});

function applyFilter(): void {
  // 空文字のパラメータはURLに含めない
  const params = Object.fromEntries(
    Object.entries(filterForm).filter(([, v]) => v !== ''),
  );
  router.get('/daily-reports', params, { preserveState: true, replace: true });
}

function clearFilter(): void {
  filterForm.cohort_id = '';
  filterForm.date_from = '';
  filterForm.date_to = '';
  router.get('/daily-reports', {}, { preserveState: true, replace: true });
}

function goToReport(id: number): void {
  router.visit(`/daily-reports/${id}`);
}
</script>
