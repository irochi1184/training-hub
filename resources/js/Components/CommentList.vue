<template>
  <div class="space-y-4">
    <p v-if="comments.length === 0" class="text-sm text-slate-400 py-4 text-center">
      コメントはまだありません
    </p>

    <div
      v-for="comment in comments"
      :key="comment.id"
      class="flex gap-3"
    >
      <div class="shrink-0 w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
        {{ comment.user?.name?.charAt(0) ?? '?' }}
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-baseline gap-2 mb-1">
          <span class="text-sm font-medium text-slate-800">{{ comment.user?.name ?? '不明' }}</span>
          <span class="text-xs text-slate-400">{{ formatDateTime(comment.created_at) }}</span>
        </div>
        <p class="text-sm text-slate-700 whitespace-pre-wrap break-words">{{ comment.body }}</p>
        <button
          v-if="canDelete && comment.user_id === currentUserId"
          type="button"
          class="mt-1 text-xs text-slate-400 hover:text-red-600 transition-colors"
          @click="requestDelete(comment.id)"
        >
          削除
        </button>
      </div>
    </div>
  </div>

  <ConfirmDialog
    v-model="showDeleteDialog"
    title="コメントの削除"
    message="このコメントを削除します。この操作は取り消せません。"
    confirm-label="削除"
    :danger="true"
    @confirm="doDelete"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { DailyReportComment } from '@/types';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import { formatDateTime } from '@/utils/formatDate';

const props = defineProps<{
  comments: DailyReportComment[];
  currentUserId?: number;
  canDelete?: boolean;
  deleteUrlPrefix?: string;
}>();

const showDeleteDialog = ref(false);
const targetCommentId = ref<number | null>(null);

function requestDelete(commentId: number): void {
  targetCommentId.value = commentId;
  showDeleteDialog.value = true;
}

function doDelete(): void {
  if (!targetCommentId.value || !props.deleteUrlPrefix) return;
  router.delete(`${props.deleteUrlPrefix}${targetCommentId.value}`, {
    preserveScroll: true,
  });
}
</script>
