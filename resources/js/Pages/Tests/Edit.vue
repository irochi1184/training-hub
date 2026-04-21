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
              コホート <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.cohort_id"
              required
              class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            >
              <option v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                {{ cohort.name }}
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

        <!-- 問題エディタ -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">問題</h2>
            <button
              type="button"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-indigo-600 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 transition-colors"
              @click="addQuestion"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              問題を追加
            </button>
          </div>

          <div
            v-if="form.questions.length === 0"
            class="bg-white rounded-lg border-2 border-dashed border-slate-300 p-8 text-center text-slate-400"
          >
            <p class="text-sm">問題がありません。「問題を追加」ボタンで追加してください。</p>
          </div>

          <div
            v-for="(question, qIndex) in form.questions"
            :key="qIndex"
            class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6"
          >
            <div class="flex items-center justify-between mb-4">
              <span class="text-sm font-semibold text-slate-700">第 {{ qIndex + 1 }} 問</span>
              <button
                type="button"
                class="text-xs text-red-500 hover:text-red-700 transition-colors"
                @click="removeQuestion(qIndex)"
              >
                この問題を削除
              </button>
            </div>

            <div class="mb-4">
              <label class="block text-xs font-medium text-slate-500 mb-1">
                問題文 <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="question.body"
                rows="2"
                required
                class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none resize-none"
              />
            </div>

            <div class="mb-4 flex items-center gap-3">
              <label class="text-xs font-medium text-slate-500">配点</label>
              <input
                v-model.number="question.score"
                type="number"
                min="1"
                required
                class="w-20 rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              />
              <span class="text-xs text-slate-400">点</span>
            </div>

            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-medium text-slate-500">選択肢</label>
                <button
                  type="button"
                  class="text-xs text-indigo-600 hover:underline"
                  @click="addChoice(qIndex)"
                >
                  + 選択肢を追加
                </button>
              </div>

              <div class="space-y-2">
                <div
                  v-for="(choice, cIndex) in question.choices"
                  :key="cIndex"
                  class="flex items-center gap-3"
                >
                  <input
                    type="checkbox"
                    v-model="choice.is_correct"
                    class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500"
                  />
                  <input
                    v-model="choice.body"
                    type="text"
                    required
                    class="flex-1 rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                    :class="choice.is_correct ? 'bg-emerald-50 border-emerald-300' : ''"
                  />
                  <span v-if="choice.is_correct" class="text-xs text-emerald-600 font-medium shrink-0">正解</span>
                  <button
                    type="button"
                    class="text-slate-400 hover:text-red-500 transition-colors shrink-0"
                    @click="removeChoice(qIndex, cIndex)"
                    :disabled="question.choices.length <= 2"
                    :class="question.choices.length <= 2 ? 'opacity-30 cursor-not-allowed' : ''"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
              <p class="mt-1 text-xs text-slate-400">チェックボックスで正解を選択してください</p>
            </div>
          </div>
        </div>

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
import type { Test, Cohort } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  test: Test;
  cohorts: Cohort[];
}>();

interface ChoiceForm {
  id?: number;
  body: string;
  is_correct: boolean;
}

interface QuestionForm {
  id?: number;
  body: string;
  score: number;
  choices: ChoiceForm[];
}

// 既存データで初期化
const form = useForm<{
  title: string;
  description: string;
  cohort_id: number | string;
  time_limit_minutes: number | null;
  opens_at: string;
  closes_at: string;
  questions: QuestionForm[];
}>({
  title: props.test.title,
  description: props.test.description ?? '',
  cohort_id: props.test.cohort_id,
  time_limit_minutes: props.test.time_limit_minutes ?? null,
  opens_at: props.test.opens_at ? toDatetimeLocal(props.test.opens_at) : '',
  closes_at: props.test.closes_at ? toDatetimeLocal(props.test.closes_at) : '',
  questions: (props.test.questions ?? []).map((q) => ({
    id: q.id,
    body: q.body,
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

function addQuestion(): void {
  form.questions.push({ body: '', score: 1, choices: [{ body: '', is_correct: false }, { body: '', is_correct: false }] });
}

function removeQuestion(index: number): void {
  form.questions.splice(index, 1);
}

function addChoice(qIndex: number): void {
  form.questions[qIndex].choices.push({ body: '', is_correct: false });
}

function removeChoice(qIndex: number, cIndex: number): void {
  if (form.questions[qIndex].choices.length <= 2) return;
  form.questions[qIndex].choices.splice(cIndex, 1);
}

function submit(): void {
  form.put(`/tests/${props.test.id}`);
}
</script>
