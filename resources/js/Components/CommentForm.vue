<template>
  <form @submit.prevent="submit" class="space-y-3">
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">コメントを追加</label>
      <textarea
        v-model="form.body"
        rows="3"
        placeholder="コメントを入力してください"
        class="block w-full rounded-lg border px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors resize-none"
        :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-400': form.errors.body, 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500': !form.errors.body }"
        :disabled="form.processing"
      />
      <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">{{ form.errors.body }}</p>
    </div>

    <div class="flex justify-end">
      <button
        type="submit"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
        :disabled="form.processing || !form.body.trim()"
      >
        {{ form.processing ? '送信中...' : '送信' }}
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
  postUrl: string;
}>();

const emit = defineEmits<{
  submitted: [];
}>();

const form = useForm({
  body: '',
});

function submit(): void {
  form.post(props.postUrl, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('body');
      emit('submitted');
    },
  });
}
</script>
