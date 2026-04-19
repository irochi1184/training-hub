<template>
  <Transition
    enter-active-class="transition-all duration-300"
    enter-from-class="opacity-0 -translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition-all duration-300"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-2"
  >
    <div v-if="visible" class="px-6 pt-4">
      <div
        class="flex items-start gap-3 rounded-md px-4 py-3 text-sm font-medium"
        :class="isSuccess ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'"
      >
        <!-- アイコン -->
        <span class="shrink-0 mt-0.5">
          <svg v-if="isSuccess" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <svg v-else class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
          </svg>
        </span>

        <span class="flex-1">{{ message }}</span>

        <!-- 閉じるボタン -->
        <button
          type="button"
          class="shrink-0 ml-2 opacity-60 hover:opacity-100 transition-opacity"
          @click="visible = false"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

const page = usePage<PageProps>();
const visible = ref(false);

// 現在のメッセージと種別
const message = ref('');
const isSuccess = ref(true);

let autoHideTimer: ReturnType<typeof setTimeout> | null = null;

function show(msg: string, success: boolean): void {
  if (autoHideTimer) clearTimeout(autoHideTimer);
  message.value = msg;
  isSuccess.value = success;
  visible.value = true;
  autoHideTimer = setTimeout(() => {
    visible.value = false;
  }, 3000);
}

// flash の変化を監視して表示
watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return;
    if (flash.success) show(flash.success, true);
    else if (flash.error) show(flash.error, false);
  },
  { deep: true },
);

// 初回マウント時にも確認
onMounted(() => {
  const flash = page.props.flash;
  if (!flash) return;
  if (flash.success) show(flash.success, true);
  else if (flash.error) show(flash.error, false);
});
</script>
