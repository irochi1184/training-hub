<template>
  <AppLayout>
    <div class="max-w-6xl">
      <!-- ページヘッダー -->
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">受講生一覧</h1>
        <p class="text-sm text-slate-500">全 {{ students.total }} 名</p>
      </div>

      <!-- フィルター -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-5 mb-5">
        <form @submit.prevent="applyFilter" class="flex items-end gap-4">
          <!-- コホート絞り込み -->
          <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1">コホート</label>
            <select
              v-model="filterForm.cohort_id"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            >
              <option value="">すべて</option>
              <option v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                {{ cohort.name }}
              </option>
            </select>
          </div>

          <!-- 名前検索 -->
          <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1">名前・メール</label>
            <input
              v-model="filterForm.search"
              type="text"
              placeholder="部分一致で検索"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            />
          </div>

          <!-- 絞り込みボタン -->
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
        :empty="students.data.length === 0"
        empty-message="条件に一致する受講生がいません"
        :col-span="5"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">名前</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">メール</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">コホート</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">最新理解度</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">状態</th>
        </template>

        <template #body>
          <tr
            v-for="student in students.data"
            :key="student.id"
            class="hover:bg-slate-50 transition-colors"
          >
            <td class="px-4 py-3">
              <Link
                :href="`/students/${student.id}`"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
              >
                {{ student.name }}
              </Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ student.email }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">
              {{ student.latest_enrollment?.cohort?.name ?? '—' }}
            </td>
            <td class="px-4 py-3">
              <UnderstandingBadge
                v-if="student.latest_understanding_level"
                :level="student.latest_understanding_level"
              />
              <span v-else class="text-sm text-slate-400">—</span>
            </td>
            <td class="px-4 py-3">
              <span
                v-if="student.has_unresolved_alert"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
              >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                要注意
              </span>
              <span v-else class="text-sm text-slate-400">—</span>
            </td>
          </tr>
        </template>
      </DataTable>

      <!-- ページネーション -->
      <Pagination :data="students" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import type { Cohort, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import UnderstandingBadge from '@/Components/UnderstandingBadge.vue';

// 受講生一覧用の拡張型（バックエンドからの追加フィールド含む）
interface StudentListItem {
  id: number;
  name: string;
  email: string;
  latest_understanding_level: number | null;
  has_unresolved_alert: boolean;
  latest_enrollment?: {
    cohort?: Cohort;
  };
}

const props = defineProps<{
  students: PaginatedData<StudentListItem>;
  cohorts: Cohort[];
  filters: {
    cohort_id?: string;
    search?: string;
  };
}>();

const filterForm = reactive({
  cohort_id: props.filters.cohort_id ?? '',
  search: props.filters.search ?? '',
});

function applyFilter(): void {
  router.get('/students', filterForm, { preserveState: true, replace: true });
}

function clearFilter(): void {
  filterForm.cohort_id = '';
  filterForm.search = '';
  router.get('/students', {}, { preserveState: true, replace: true });
}
</script>
