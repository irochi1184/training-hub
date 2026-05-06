<template>
  <AppLayout>
    <div class="max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900">テスト編集</h1>
        <Link href="/tests" class="text-sm text-slate-500 hover:text-slate-800">
          ← 一覧に戻る
        </Link>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- 基本情報 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 space-y-5">
          <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-100 pb-3">基本情報</h2>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              タイトル <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
              :class="form.errors.title
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              説明 <span class="text-xs text-slate-400 font-normal">（任意）</span>
            </label>
            <textarea
              v-model="form.description"
              rows="2"
              class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors resize-none"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              カリキュラム <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.curriculum_id"
              required
              class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="curriculum in curricula" :key="curriculum.id" :value="curriculum.id">
                {{ curriculum.name }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">
                制限時間（分）<span class="text-xs text-slate-400 font-normal">（空欄=無制限）</span>
              </label>
              <input
                v-model.number="form.time_limit_minutes"
                type="number"
                min="1"
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
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
            {{ form.processing ? '保存中...' : '変更を保存する' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { Test, Curriculum } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuestionEditor from '@/Components/QuestionEditor.vue';
import type { QuestionForm } from '@/Components/QuestionEditor.vue';

const props = defineProps<{
  test: Test;
  curricula: Curriculum[];
}>();

// 既存データで初期化
const form = useForm<{
  title: string;
  description: string;
  curriculum_id: number | string;
  time_limit_minutes: number | null;
  opens_at: string;
  closes_at: string;
  questions: QuestionForm[];
}>({
  title: props.test.title,
  description: props.test.description ?? '',
  curriculum_id: props.test.curriculum_id,
  time_limit_minutes: props.test.time_limit_minutes ?? null,
  opens_at: props.test.opens_at ? toDatetimeLocal(props.test.opens_at) : '',
  closes_at: props.test.closes_at ? toDatetimeLocal(props.test.closes_at) : '',
  questions: (props.test.questions ?? []).map((q) => ({
    id: q.id,
    body: q.body,
    question_type: q.question_type ?? 'single',
    score: q.score,
    choices: (q.choices ?? []).map((c) => ({
      id: c.id,
      body: c.body,
      is_correct: c.is_correct ?? false,
    })),
  })),
});

// datetime-local 形式への変換（ブラウザ入力用）
function toDatetimeLocal(isoStr: string): string {
  return new Date(isoStr).toISOString().slice(0, 16);
}

function submit(): void {
  form.put(`/tests/${props.test.id}`);
}
</script>
