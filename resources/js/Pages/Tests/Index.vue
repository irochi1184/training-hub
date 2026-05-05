<template>
  <AppLayout>
    <div class="max-w-6xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">テスト一覧</h1>
        <Link
          v-if="canCreate"
          href="/tests/create"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          テストを作成
        </Link>
      </div>

      <DataTable
        :empty="tests.data.length === 0"
        empty-message="テストがまだ作成されていません"
        :col-span="canCreate ? 7 : 6"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">タイトル</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">カリキュラム</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">問題数</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">公開期間</th>
          <th v-if="canCreate" class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">受験者数</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">状態</th>
          <th class="px-4 py-3"></th>
        </template>

        <template #body>
          <tr
            v-for="test in tests.data"
            :key="test.id"
            class="hover:bg-slate-50 transition-colors"
          >
            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ test.title }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ test.curriculum?.name ?? '—' }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ test.questions_count ?? 0 }} 問</td>
            <td class="px-4 py-3 text-xs text-slate-500">
              <span v-if="test.opens_at && test.closes_at">
                {{ formatDate(test.opens_at) }}〜{{ formatDate(test.closes_at) }}
              </span>
              <span v-else-if="test.opens_at">{{ formatDate(test.opens_at) }}〜</span>
              <span v-else class="text-slate-400">無期限</span>
            </td>
            <td v-if="canCreate" class="px-4 py-3 text-sm text-slate-600">
              {{ test.submissions_count ?? 0 }} 名
            </td>
            <td class="px-4 py-3">
              <StatusBadge :color="testStatusColor(test)" :label="testStatusLabel(test)" />
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Link
                  v-if="isStudent && isAvailable(test)"
                  :href="`/tests/${test.id}/take`"
                  class="text-sm font-medium text-white bg-indigo-600 px-3 py-1.5 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
                >
                  受験する
                </Link>

                <!-- admin/instructor: 編集・削除（受験者がいると編集も削除も不可） -->
                <template v-if="canCreate">
                  <!-- 受験者がいる場合のみ分析リンクを表示 -->
                  <Link
                    v-if="(test.submissions_count ?? 0) > 0"
                    :href="`/tests/${test.id}/analytics`"
                    class="text-sm text-slate-600 hover:text-slate-800"
                  >
                    分析
                  </Link>
                  <Link
                    v-if="(test.submissions_count ?? 0) === 0"
                    :href="`/tests/${test.id}/edit`"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                  >
                    編集
                  </Link>
                  <span
                    v-if="(test.submissions_count ?? 0) > 0"
                    class="text-sm text-slate-400 cursor-not-allowed"
                    title="受験者がいるため編集できません"
                  >
                    編集
                  </span>
                  <button
                    type="button"
                    class="text-sm text-red-600 hover:underline"
                    :disabled="(test.submissions_count ?? 0) > 0"
                    :class="(test.submissions_count ?? 0) > 0 ? 'opacity-40 cursor-not-allowed' : ''"
                    :title="(test.submissions_count ?? 0) > 0 ? '受験者がいるため削除できません' : ''"
                    @click="requestDelete(test)"
                  >
                    削除
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </template>

        <template v-if="canCreate" #empty-action>
          <Link
            href="/tests/create"
            class="mt-2 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            最初のテストを作成
          </Link>
        </template>
      </DataTable>

      <Pagination :data="tests" />
    </div>

    <ConfirmDialog
      v-model="showDeleteDialog"
      title="テストの削除"
      :message="`「${deleteTarget?.title}」を削除します。この操作は取り消せません。`"
      confirm-label="削除する"
      :danger="true"
      @confirm="doDelete"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import type { Test, PageProps, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { formatDate } from '@/utils/formatDate';

const props = defineProps<{
  tests: PaginatedData<Test>;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const canCreate = computed(() => user.value.role === 'admin' || user.value.role === 'instructor');
const isStudent = computed(() => user.value.role === 'student');

function isAvailable(test: Test): boolean {
  const now = new Date();
  if (test.opens_at && new Date(test.opens_at) > now) return false;
  if (test.closes_at && new Date(test.closes_at) < now) return false;
  return true;
}

function testStatusColor(test: Test): 'green' | 'yellow' | 'gray' {
  const now = new Date();
  if (test.closes_at && new Date(test.closes_at) < now) return 'gray';
  if (test.opens_at && new Date(test.opens_at) > now) return 'yellow';
  return 'green';
}

function testStatusLabel(test: Test): string {
  const now = new Date();
  if (test.closes_at && new Date(test.closes_at) < now) return '終了';
  if (test.opens_at && new Date(test.opens_at) > now) return '公開前';
  return '受験可能';
}

const showDeleteDialog = ref(false);
const deleteTarget = ref<Test | null>(null);

function requestDelete(test: Test): void {
  deleteTarget.value = test;
  showDeleteDialog.value = true;
}

function doDelete(): void {
  if (!deleteTarget.value) return;
  router.delete(`/tests/${deleteTarget.value.id}`);
}
</script>
