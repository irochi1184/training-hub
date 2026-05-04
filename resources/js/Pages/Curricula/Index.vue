<template>
  <AppLayout>
    <div class="max-w-6xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">カリキュラム管理</h1>
        <Link
          href="/curricula/create"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          カリキュラムを作成
        </Link>
      </div>

      <DataTable
        :empty="curricula.data.length === 0"
        empty-message="カリキュラムがありません"
        :col-span="6"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">名称</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">担当講師</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">期間</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">受講生</th>
          <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">テスト</th>
          <th class="px-4 py-3"></th>
        </template>

        <template #body>
          <tr
            v-for="curriculum in curricula.data"
            :key="curriculum.id"
            class="hover:bg-slate-50 transition-colors"
          >
            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ curriculum.name }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ curriculum.instructor?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-xs text-slate-500">
              {{ formatDate(curriculum.starts_on) }} 〜 {{ formatDate(curriculum.ends_on) }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-slate-600">{{ curriculum.enrollments_count ?? 0 }} 名</td>
            <td class="px-4 py-3 text-sm text-right text-slate-600">{{ curriculum.tests_count ?? 0 }} 本</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-3">
                <Link
                  :href="`/curricula/${curriculum.id}/edit`"
                  class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                  編集
                </Link>
                <button
                  type="button"
                  class="text-sm text-red-600 hover:underline"
                  @click="requestDelete(curriculum)"
                >
                  削除
                </button>
              </div>
            </td>
          </tr>
        </template>
      </DataTable>

      <Pagination :data="curricula" />
    </div>

    <ConfirmDialog
      v-model="showDeleteDialog"
      title="カリキュラムの削除"
      :message="`「${deleteTarget?.name}」を削除します。関連する受講登録・日報・テストはそのまま残りますが、一覧からは非表示になります。`"
      confirm-label="削除する"
      :danger="true"
      @confirm="doDelete"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import type { Curriculum, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { formatDate } from '@/utils/formatDate';

interface CurriculumRow extends Curriculum {
  enrollments_count?: number;
  tests_count?: number;
}

defineProps<{
  curricula: PaginatedData<CurriculumRow>;
}>();

const showDeleteDialog = ref(false);
const deleteTarget = ref<CurriculumRow | null>(null);

function requestDelete(curriculum: CurriculumRow): void {
  deleteTarget.value = curriculum;
  showDeleteDialog.value = true;
}

function doDelete(): void {
  if (!deleteTarget.value) return;
  router.delete(`/curricula/${deleteTarget.value.id}`);
}

</script>
