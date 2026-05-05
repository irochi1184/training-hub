<template>
  <AppLayout>
    <div class="max-w-3xl">
      <!-- 戻るリンク -->
      <Link href="/announcements" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-4">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        お知らせ一覧に戻る
      </Link>

      <article class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 sm:p-8">
        <!-- ヘッダー -->
        <div class="mb-6">
          <div class="flex items-center gap-2 mb-2">
            <span
              v-if="announcement.priority === 'important'"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
            >
              重要
            </span>
          </div>
          <h1 class="text-xl font-bold text-slate-900">{{ announcement.title }}</h1>
          <p class="mt-2 text-xs text-slate-500">
            {{ announcement.creator?.name }} ・ {{ formatDate(announcement.published_at || announcement.created_at) }}
          </p>
        </div>

        <!-- 本文 -->
        <div class="prose prose-sm prose-slate max-w-none whitespace-pre-wrap text-slate-700 leading-relaxed">
          {{ announcement.body }}
        </div>
      </article>

      <!-- 管理者向けアクション -->
      <div v-if="canEdit" class="mt-4 flex items-center gap-3">
        <Link
          :href="`/announcements/${announcement.id}/edit`"
          class="text-sm text-indigo-600 hover:text-indigo-800"
        >
          編集
        </Link>
        <button
          type="button"
          class="text-sm text-red-600 hover:underline"
          @click="showDeleteDialog = true"
        >
          削除
        </button>
      </div>

      <ConfirmDialog
        v-model="showDeleteDialog"
        title="お知らせの削除"
        :message="`「${announcement.title}」を削除します。この操作は取り消せません。`"
        confirm-label="削除する"
        :danger="true"
        @confirm="doDelete"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import type { Announcement, PageProps } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps<{
  announcement: Announcement;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const canEdit = computed(() => {
  if (user.value.role === 'admin') return true;
  return user.value.role === 'instructor' && props.announcement.created_by === user.value.id;
});

const showDeleteDialog = ref(false);

function doDelete(): void {
  router.delete(`/announcements/${props.announcement.id}`);
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>
