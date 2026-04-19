<template>
  <div class="space-y-4">
    <!-- コメントなし -->
    <p v-if="comments.length === 0" class="text-sm text-gray-400 py-4 text-center">
      コメントはまだありません
    </p>

    <!-- コメント一覧 -->
    <div
      v-for="comment in comments"
      :key="comment.id"
      class="flex gap-3"
    >
      <!-- アバター代わりのイニシャル -->
      <div class="shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-semibold">
        {{ comment.user?.name?.charAt(0) ?? '?' }}
      </div>

      <div class="flex-1 min-w-0">
        <!-- 名前・日時 -->
        <div class="flex items-baseline gap-2 mb-1">
          <span class="text-sm font-medium text-gray-800">{{ comment.user?.name ?? '不明' }}</span>
          <span class="text-xs text-gray-400">{{ formatDate(comment.created_at) }}</span>
        </div>

        <!-- 本文 -->
        <p class="text-sm text-gray-700 whitespace-pre-wrap break-words">{{ comment.body }}</p>

        <!-- 削除ボタン（自分のコメントかつ canDelete=true の場合） -->
        <button
          v-if="canDelete && comment.user_id === currentUserId"
          type="button"
          class="mt-1 text-xs text-gray-400 hover:text-red-600 transition-colors"
          @click="requestDelete(comment.id)"
        >
          削除
        </button>
      </div>
    </div>
  </div>

  <!-- 削除確認ダイアログ -->
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

const props = defineProps<{
  comments: DailyReportComment[];
  currentUserId?: number;
  canDelete?: boolean;
  // コメント削除URL生成用のプレフィックス例: /daily-reports/1/comments/
  deleteUrlPrefix?: string;
}>();

const showDeleteDialog = ref(false);
const targetCommentId = ref<number | null>(null);

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString('ja-JP', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}

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
