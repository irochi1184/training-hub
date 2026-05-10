<template>
  <AppLayout>
    <div class="max-w-6xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">ユーザー管理</h1>
        <Link
          href="/users/create"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          ユーザーを追加
        </Link>
      </div>

      <!-- フィルター -->
      <div class="flex items-center gap-3 mb-4">
        <select
          :value="filters.role"
          class="rounded-lg border-slate-300 text-sm"
          @change="applyFilter('role', ($event.target as HTMLSelectElement).value)"
        >
          <option value="">全ロール</option>
          <option value="admin">管理者</option>
          <option value="instructor">講師</option>
          <option value="student">受講生</option>
        </select>
        <input
          type="text"
          :value="filters.search"
          placeholder="名前で検索..."
          class="rounded-lg border-slate-300 text-sm w-48"
          @input="applyFilter('search', ($event.target as HTMLInputElement).value)"
        />
      </div>

      <DataTable
        :empty="users.data.length === 0"
        empty-message="ユーザーがいません"
        :col-span="5"
      >
        <template #head>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">名前</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">メールアドレス</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">ロール</th>
          <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">作成日</th>
          <th class="px-4 py-3"></th>
        </template>

        <template #body>
          <tr
            v-for="user in users.data"
            :key="user.id"
            class="hover:bg-slate-50 transition-colors"
          >
            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ user.name }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ user.email }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="roleBadgeClass(user.role)"
              >
                {{ roleLabel(user.role) }}
              </span>
            </td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ formatDate(user.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-3">
                <Link
                  :href="`/users/${user.id}/edit`"
                  class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                  編集
                </Link>
                <button
                  type="button"
                  class="text-sm text-red-600 hover:underline"
                  @click="requestDelete(user)"
                >
                  削除
                </button>
              </div>
            </td>
          </tr>
        </template>
      </DataTable>

      <Pagination :data="users" />
    </div>

    <ConfirmDialog
      v-model="showDeleteDialog"
      title="ユーザーの削除"
      :message="`「${deleteTarget?.name}」を削除します。この操作は元に戻せません。`"
      confirm-label="削除する"
      :danger="true"
      @confirm="doDelete"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import type { User, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { formatDate } from '@/utils/formatDate';

interface UserRow extends User {
  created_at?: string;
}

const props = defineProps<{
  users: PaginatedData<UserRow>;
  filters: { role: string; search: string };
}>();

const showDeleteDialog = ref(false);
const deleteTarget = ref<UserRow | null>(null);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

function applyFilter(key: string, value: string): void {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get('/users', { ...props.filters, [key]: value }, { preserveState: true, replace: true });
  }, key === 'search' ? 300 : 0);
}

function roleBadgeClass(role: string): string {
  switch (role) {
    case 'admin': return 'bg-purple-100 text-purple-700';
    case 'instructor': return 'bg-blue-100 text-blue-700';
    case 'student': return 'bg-green-100 text-green-700';
    default: return 'bg-slate-100 text-slate-700';
  }
}

function roleLabel(role: string): string {
  switch (role) {
    case 'admin': return '管理者';
    case 'instructor': return '講師';
    case 'student': return '受講生';
    default: return role;
  }
}

function requestDelete(user: UserRow): void {
  deleteTarget.value = user;
  showDeleteDialog.value = true;
}

function doDelete(): void {
  if (!deleteTarget.value) return;
  router.delete(`/users/${deleteTarget.value.id}`);
}
</script>
