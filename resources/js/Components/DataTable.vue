<template>
  <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
      <!-- ヘッダー -->
      <thead class="bg-gray-50">
        <tr>
          <slot name="head" />
        </tr>
      </thead>

      <!-- ボディ -->
      <tbody class="divide-y divide-gray-100">
        <slot name="body" />

        <!-- 空状態 -->
        <tr v-if="empty">
          <td :colspan="colSpan" class="py-12 text-center text-gray-400">
            <div class="flex flex-col items-center gap-2">
              <svg class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>{{ emptyMessage }}</span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  empty?: boolean;
  emptyMessage?: string;
  colSpan?: number;
}>();

// emptyMessage のデフォルト値
const emptyMessage = props.emptyMessage ?? 'データがありません';
const colSpan = props.colSpan ?? 10;
</script>
