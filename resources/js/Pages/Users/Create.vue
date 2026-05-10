<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">ユーザーを追加</h1>

      <form @submit.prevent="submit" class="space-y-5 bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">名前</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-lg border-slate-300 text-sm"
            required
          />
          <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">メールアドレス</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full rounded-lg border-slate-300 text-sm"
            required
          />
          <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">パスワード</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full rounded-lg border-slate-300 text-sm"
            required
          />
          <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">ロール</label>
          <select v-model="form.role" class="w-full rounded-lg border-slate-300 text-sm" required>
            <option value="">選択してください</option>
            <option value="admin">管理者</option>
            <option value="instructor">講師</option>
            <option value="student">受講生</option>
          </select>
          <p v-if="form.errors.role" class="mt-1 text-xs text-red-500">{{ form.errors.role }}</p>
        </div>

        <div class="flex items-center gap-3 pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            作成する
          </button>
          <Link href="/users" class="text-sm text-slate-500 hover:text-slate-700">キャンセル</Link>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: '',
});

function submit(): void {
  form.post('/users');
}
</script>
