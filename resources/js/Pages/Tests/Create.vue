<template>
  <AppLayout>
    <div class="max-w-4xl">
      <h1 class="text-2xl font-bold text-slate-900 mb-6">テスト作成</h1>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- 基本情報 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 space-y-5">
          <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-100 pb-3">基本情報</h2>

          <!-- タイトル -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              タイトル <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="例: 第1回 HTTPの基礎"
              class="block w-full rounded border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors"
              :class="form.errors.title
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
          </div>

          <!-- 説明 -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              説明 <span class="text-xs text-slate-400 font-normal">（任意）</span>
            </label>
            <textarea
              v-model="form.description"
              rows="2"
              placeholder="テストの補足説明があれば記載してください"
              class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors resize-none"
            />
          </div>

          <!-- カリキュラム -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              カリキュラム <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.curriculum_id"
              required
              class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
              :class="form.errors.curriculum_id
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
            >
              <option value="" disabled>選択してください</option>
              <option v-for="curriculum in curricula" :key="curriculum.id" :value="curriculum.id">
                {{ curriculum.name }}
              </option>
            </select>
            <p v-if="form.errors.curriculum_id" class="mt-1 text-xs text-red-600">{{ form.errors.curriculum_id }}</p>
          </div>

          <!-- 制限時間・公開期間・受験回数 -->
          <div class="grid grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                制限時間（分）
                <span class="text-xs text-slate-400 font-normal">（空欄=無制限）</span>
              </label>
              <input
                v-model.number="form.time_limit_minutes"
                type="number"
                min="1"
                placeholder="例: 30"
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                受験回数上限
                <span class="text-xs text-slate-400 font-normal">（空欄=1回、0=無制限）</span>
              </label>
              <input
                v-model.number="form.max_attempts"
                type="number"
                min="0"
                placeholder="空欄: 1回のみ"
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">公開開始日時</label>
              <input
                v-model="form.opens_at"
                type="datetime-local"
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">公開終了日時</label>
              <input
                v-model="form.closes_at"
                type="datetime-local"
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>
        </div>

        <QuestionEditor :questions="form.questions" />

        <!-- 送信ボタン -->
        <div class="flex justify-end gap-3">
          <Link
            href="/tests"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 transition-colors"
          >
            キャンセル
          </Link>
          <button
            type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            :disabled="form.processing"
          >
            {{ form.processing ? '保存中...' : 'テストを作成する' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { Curriculum } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuestionEditor from '@/Components/QuestionEditor.vue';
import type { QuestionForm } from '@/Components/QuestionEditor.vue';

const props = defineProps<{
  curricula: Curriculum[];
}>();

const form = useForm<{
  title: string;
  description: string;
  curriculum_id: string | number;
  time_limit_minutes: number | null;
  max_attempts: number | null;
  opens_at: string;
  closes_at: string;
  questions: QuestionForm[];
}>({
  title: '',
  description: '',
  curriculum_id: '',
  time_limit_minutes: null,
  max_attempts: null,
  opens_at: '',
  closes_at: '',
  questions: [] as QuestionForm[],
});

function submit(): void {
  form.post('/tests');
}
</script>
