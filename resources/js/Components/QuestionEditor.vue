<template>
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
      v-if="questions.length === 0"
      class="bg-white rounded-lg border-2 border-dashed border-slate-300 p-8 text-center text-slate-400"
    >
      <p class="text-sm">問題がまだありません。「問題を追加」ボタンで追加してください。</p>
    </div>

    <div
      v-for="(question, qIndex) in questions"
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
          placeholder="問題文を入力してください"
          class="block w-full rounded border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none resize-none"
        />
      </div>

      <div class="mb-4 flex items-center gap-4">
        <div class="flex items-center gap-3">
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
        <div class="flex items-center gap-3">
          <label class="text-xs font-medium text-slate-500">形式</label>
          <select
            v-model="question.question_type"
            class="rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
          >
            <option value="single">単一選択</option>
            <option value="multiple">複数選択</option>
          </select>
        </div>
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
              :title="'正解にする'"
            />
            <input
              v-model="choice.body"
              type="text"
              required
              placeholder="選択肢を入力"
              class="flex-1 rounded border border-slate-300 px-3 py-1.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
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

        <p class="mt-1 text-xs text-slate-400">
          {{ question.question_type === 'multiple' ? '複数の正解をチェックしてください' : 'チェックボックスで正解を1つ選択してください' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
export interface ChoiceForm {
  id?: number;
  body: string;
  is_correct: boolean;
}

export interface QuestionForm {
  id?: number;
  body: string;
  question_type: 'single' | 'multiple';
  score: number;
  choices: ChoiceForm[];
}

const props = defineProps<{
  questions: QuestionForm[];
}>();

function defaultChoices(): ChoiceForm[] {
  return [
    { body: '', is_correct: false },
    { body: '', is_correct: false },
  ];
}

function addQuestion(): void {
  props.questions.push({ body: '', question_type: 'single', score: 1, choices: defaultChoices() });
}

function removeQuestion(index: number): void {
  props.questions.splice(index, 1);
}

function addChoice(qIndex: number): void {
  props.questions[qIndex].choices.push({ body: '', is_correct: false });
}

function removeChoice(qIndex: number, cIndex: number): void {
  if (props.questions[qIndex].choices.length <= 2) return;
  props.questions[qIndex].choices.splice(cIndex, 1);
}
</script>
