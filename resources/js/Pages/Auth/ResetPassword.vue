<template>
  <AuthLayout>
    <h2 class="text-xl font-semibold text-slate-800 mb-6 text-center">新しいパスワードを設定</h2>

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
          class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
          新しいパスワード
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          required
          class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors"
          :class="form.errors.password
            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
          placeholder="8文字以上"
        />
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">
          パスワード（確認）
        </label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
          placeholder="パスワードを再入力"
        />
      </div>

      <button
        type="submit"
        class="w-full py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
        :disabled="form.processing"
      >
        {{ form.processing ? '処理中...' : 'パスワードをリセット' }}
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
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const props = defineProps<{
  token: string;
  email: string;
}>();

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

function submit(): void {
  form.post('/reset-password', {
    onFinish: () => {
      form.reset('password', 'password_confirmation');
    },
  });
}
</script>
