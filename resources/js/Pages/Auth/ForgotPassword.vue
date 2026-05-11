<template>
  <AuthLayout>
    <h2 class="text-xl font-semibold text-slate-800 mb-2 text-center">パスワードリセット</h2>
    <p class="text-sm text-slate-500 text-center mb-6">
      登録済みのメールアドレスを入力してください。リセットリンクを送信します。
    </p>

    <!-- 成功メッセージ -->
    <div v-if="flash?.success" class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
      {{ flash.success }}
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
          メールアドレス
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors"
          :class="form.errors.email
            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
          placeholder="example@company.com"
        />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
      </div>

      <button
        type="submit"
        class="w-full py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
        :disabled="form.processing"
      >
        {{ form.processing ? '送信中...' : 'リセットリンクを送信' }}
      </button>

      <div class="text-center">
        <Link href="/login" class="text-sm text-slate-500 hover:text-slate-700">
          ログイン画面に戻る
        </Link>
      </div>
    </form>
  </AuthLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const page = usePage<PageProps>();
const flash = computed(() => page.props.flash);

const form = useForm({
  email: '',
});

function submit(): void {
  form.post('/forgot-password');
}
</script>
