<template>
  <AuthLayout>
    <h2 class="text-xl font-semibold text-slate-800 mb-6 text-center">ログイン</h2>

    <form @submit.prevent="submit" class="space-y-5">
      <!-- メールアドレス -->
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

      <!-- パスワード -->
      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
          パスワード
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
          class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors"
          :class="form.errors.password
            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
          placeholder="パスワードを入力"
        />
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
      </div>

      <!-- 全体エラー -->
      <div v-if="form.errors.general" class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ form.errors.general }}
      </div>

      <!-- ログインボタン -->
      <button
        type="submit"
        class="w-full py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
        :disabled="form.processing"
      >
        {{ form.processing ? 'ログイン中...' : 'ログイン' }}
      </button>
    </form>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const form = useForm({
  email: '',
  password: '',
});

function submit(): void {
  form.post('/login', {
    onError: () => {
      form.reset('password');
    },
  });
}
</script>
