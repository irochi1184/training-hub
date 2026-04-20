<template>
  <!-- オーバーレイ -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
        @click.self="cancel"
      >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
          <!-- タイトル -->
          <h3 class="text-base font-semibold text-gray-900 mb-2">{{ title }}</h3>

          <!-- メッセージ -->
          <p class="text-sm text-gray-600 mb-6">{{ message }}</p>

          <!-- ボタン -->
          <div class="flex justify-end gap-3">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors"
              @click="cancel"
            >
              {{ cancelLabel }}
            </button>
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-white rounded transition-colors"
              :class="danger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
              @click="confirm"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: boolean;
  title?: string;
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  danger?: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  confirm: [];
  cancel: [];
}>();

function confirm(): void {
  emit('confirm');
  emit('update:modelValue', false);
}

function cancel(): void {
  emit('cancel');
  emit('update:modelValue', false);
}
</script>
