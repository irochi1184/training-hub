<template>
  <AppLayout>
    <div class="max-w-3xl">
      <!-- 戻るリンク -->
      <Link href="/tests" class="text-sm text-slate-500 hover:text-slate-800 flex items-center gap-1 mb-4">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        テスト一覧に戻る
      </Link>

      <!-- 結果サマリー -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
        <h1 class="text-lg font-bold text-slate-900 mb-1">{{ submission.test?.title }}</h1>
        <p class="text-sm text-slate-500 mb-6">
          受験者: {{ submission.user?.name ?? '—' }}
          <span class="mx-2 text-slate-300">|</span>
          提出日時: {{ submission.submitted_at ? formatDateTime(submission.submitted_at) : '未提出' }}
        </p>

        <!-- スコア表示 -->
        <div class="flex items-center gap-6">
          <div class="text-center">
            <p class="text-5xl font-bold" :class="scoreColorClass">
              {{ submission.score ?? '—' }}
            </p>
            <p class="text-sm text-slate-500 mt-1">得点 / {{ totalScore }} 点満点</p>
          </div>
          <div class="flex-1">
            <!-- スコアバー -->
            <div class="bg-slate-100 rounded-full h-4 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :class="scoreColorClass.replace('text-', 'bg-')"
                :style="{ width: `${scorePercent}%` }"
              />
            </div>
            <p class="text-sm text-slate-500 mt-2">正解率: {{ scorePercent }}%</p>
          </div>
        </div>
      </div>

      <!-- 問題別正誤 -->
      <div class="space-y-4">
        <h2 class="text-sm font-semibold text-slate-700">問題別の結果</h2>

        <div
          v-for="(answer, index) in answersWithQuestion"
          :key="answer.id"
          class="bg-white rounded-lg border overflow-hidden"
          :class="answerBorderClass(answer.is_correct)"
        >
          <!-- 問題ヘッダー -->
          <div
            class="flex items-center justify-between px-5 py-3 text-sm"
            :class="answerHeaderClass(answer.is_correct)"
          >
            <div class="flex items-center gap-2">
              <!-- 正誤アイコン -->
              <svg v-if="answer.is_correct === true" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else-if="answer.is_correct === false" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
              </svg>
              <span class="font-medium">第 {{ index + 1 }} 問</span>
              <span class="text-xs opacity-70">{{ answer.question?.score ?? 0 }}点</span>
            </div>
            <span class="text-xs font-medium">
              {{ answer.is_correct === true ? '正解' : answer.is_correct === false ? '不正解' : '未回答' }}
            </span>
          </div>

          <!-- 問題内容 -->
          <div class="px-5 py-4">
            <p class="text-sm text-slate-800 font-medium mb-3">{{ answer.question?.body }}</p>

            <!-- 選択肢と回答状況 -->
            <div class="space-y-2">
              <div
                v-for="choice in answer.question?.choices"
                :key="choice.id"
                class="flex items-center gap-2 text-sm px-3 py-2 rounded"
                :class="choiceClass(choice, answer)"
              >
                <!-- 選択アイコン -->
                <span class="shrink-0 w-4">
                  <svg v-if="answer.choice_id === choice.id" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <span>{{ choice.body }}</span>
                <!-- 正解マーク -->
                <span v-if="choice.is_correct" class="ml-auto text-xs text-emerald-600 font-medium">正解</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Submission, Answer, Choice } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/utils/formatDate';

const props = defineProps<{
  submission: Submission;
}>();

// 問題付きの回答一覧
const answersWithQuestion = computed(() => props.submission.answers ?? []);

// 満点（全問題の配点合計）
const totalScore = computed(() => {
  const questions = props.submission.test?.questions ?? [];
  return questions.reduce((sum, q) => sum + q.score, 0);
});

// 得点率
const scorePercent = computed(() => {
  if (!props.submission.score || !totalScore.value) return 0;
  return Math.round((props.submission.score / totalScore.value) * 100);
});

// スコアに応じた色クラス
const scoreColorClass = computed(() => {
  const pct = scorePercent.value;
  if (pct >= 80) return 'text-emerald-600';
  if (pct >= 60) return 'text-yellow-600';
  return 'text-red-600';
});

// 正誤に応じたボーダー
function answerBorderClass(isCorrect: boolean | null): string {
  if (isCorrect === true) return 'border-green-200';
  if (isCorrect === false) return 'border-red-200';
  return 'border-slate-200';
}

// 正誤に応じたヘッダー背景
function answerHeaderClass(isCorrect: boolean | null): string {
  if (isCorrect === true) return 'bg-emerald-50 text-emerald-800';
  if (isCorrect === false) return 'bg-red-50 text-red-800';
  return 'bg-slate-50 text-slate-600';
}

// 選択肢の表示クラス
function choiceClass(choice: Choice, answer: Answer): string {
  const isSelected = answer.choice_id === choice.id;
  const isCorrect = choice.is_correct;

  if (isCorrect && isSelected) return 'bg-emerald-50 text-emerald-800';
  if (isCorrect && !isSelected) return 'bg-emerald-50 text-emerald-700 opacity-70';
  if (!isCorrect && isSelected) return 'bg-red-50 text-red-800';
  return 'text-slate-600';
}

</script>
