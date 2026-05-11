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
          第{{ submission.attempt }}回目
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
            <div class="flex items-center gap-4 mt-2">
              <p class="text-sm text-slate-500">正解率: {{ scorePercent }}%</p>
              <!-- 前回比較 -->
              <span v-if="previousScore !== null && previousScore !== undefined" class="text-xs font-medium" :class="scoreDiffClass">
                前回比 {{ scoreDiff >= 0 ? '+' : '' }}{{ scoreDiff }}点
              </span>
            </div>
          </div>
        </div>

        <!-- 正答率バッジ -->
        <div class="flex items-center gap-4 mt-5 pt-4 border-t border-slate-100">
          <div class="flex items-center gap-1.5 text-sm">
            <span class="w-3 h-3 rounded-full bg-emerald-400" />
            <span class="text-slate-600">正解 {{ correctCount }}問</span>
          </div>
          <div class="flex items-center gap-1.5 text-sm">
            <span class="w-3 h-3 rounded-full bg-red-400" />
            <span class="text-slate-600">不正解 {{ incorrectCount }}問</span>
          </div>
          <div v-if="unansweredCount > 0" class="flex items-center gap-1.5 text-sm">
            <span class="w-3 h-3 rounded-full bg-slate-300" />
            <span class="text-slate-600">未回答 {{ unansweredCount }}問</span>
          </div>
          <!-- 再受験ボタン -->
          <Link
            v-if="canRetake"
            :href="`/tests/${submission.test_id}/take`"
            class="ml-auto inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            もう一度受験する
            <span v-if="remainingAttempts !== null" class="text-xs opacity-80">
              （残り{{ remainingAttempts }}回）
            </span>
          </Link>
        </div>
      </div>

      <!-- 受験履歴（複数回受験時のみ表示） -->
      <div v-if="allAttempts.length > 1" class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">受験履歴（最高点: {{ bestScore }}点）</h2>
        <div class="space-y-2">
          <div
            v-for="attempt in allAttempts"
            :key="attempt.id"
            class="flex items-center justify-between px-3 py-2 rounded text-sm"
            :class="attempt.id === submission.id ? 'bg-indigo-50 text-indigo-800' : 'text-slate-600 hover:bg-slate-50'"
          >
            <span>第{{ attempt.attempt }}回目</span>
            <span class="font-medium">{{ attempt.score }}点</span>
            <span class="text-xs text-slate-400">{{ attempt.submitted_at ? formatDateTime(attempt.submitted_at) : '' }}</span>
            <Link
              v-if="attempt.id !== submission.id"
              :href="`/submissions/${attempt.id}`"
              class="text-xs text-indigo-600 hover:text-indigo-800"
            >
              詳細
            </Link>
            <span v-else class="text-xs text-indigo-500">表示中</span>
          </div>
        </div>
      </div>

      <!-- 問題別正誤 -->
      <div class="space-y-4">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-semibold text-slate-700">問題別の結果</h2>
          <div class="flex gap-1 ml-auto">
            <button
              v-for="f in filterOptions"
              :key="f.value"
              type="button"
              class="px-3 py-1 text-xs rounded-full transition-colors"
              :class="filter === f.value
                ? 'bg-indigo-600 text-white'
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              @click="filter = f.value"
            >
              {{ f.label }}
            </button>
          </div>
        </div>

        <div
          v-for="result in filteredResults"
          :key="result.id"
          class="bg-white rounded-lg border overflow-hidden"
          :class="answerBorderClass(result.is_correct)"
        >
          <!-- 問題ヘッダー -->
          <div
            class="flex items-center justify-between px-5 py-3 text-sm"
            :class="answerHeaderClass(result.is_correct)"
          >
            <div class="flex items-center gap-2">
              <!-- 正誤アイコン -->
              <svg v-if="result.is_correct === true" class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else-if="result.is_correct === false" class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <svg v-else class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
              </svg>
              <span class="font-medium">第 {{ result.position }} 問</span>
              <span class="text-xs opacity-70">{{ result.question.score }}点</span>
              <span v-if="result.question.question_type === 'multiple'" class="text-xs text-indigo-500 font-medium">複数選択</span>
            </div>
            <span class="text-xs font-medium">
              {{ result.is_correct === true ? '正解' : result.is_correct === false ? '不正解' : '未回答' }}
            </span>
          </div>

          <!-- 問題内容 -->
          <div class="px-5 py-4">
            <p class="text-sm text-slate-800 font-medium mb-3">{{ result.question.body }}</p>

            <!-- 選択肢と回答状況 -->
            <div class="space-y-2">
              <div
                v-for="choice in result.question.choices"
                :key="choice.id"
                class="flex items-center gap-2 text-sm px-3 py-2 rounded"
                :class="choiceClass(choice, result)"
              >
                <!-- 選択アイコン -->
                <span class="shrink-0 w-4">
                  <svg v-if="result.selected_choice_ids.includes(choice.id)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Submission, Answer, Choice, Question, AttemptSummary } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDateTime } from '@/utils/formatDate';

const props = defineProps<{
  submission: Submission;
  allAttempts: AttemptSummary[];
  bestScore: number | null;
  previousScore: number | null;
  canRetake: boolean;
  remainingAttempts: number | null;
}>();

interface QuestionResult {
  id: number;
  position: number;
  question: Question;
  is_correct: boolean | null;
  selected_choice_ids: number[];
}

type FilterType = 'all' | 'incorrect' | 'correct';

const filter = ref<FilterType>('all');
const filterOptions: { value: FilterType; label: string }[] = [
  { value: 'all', label: 'すべて' },
  { value: 'incorrect', label: '不正解のみ' },
  { value: 'correct', label: '正解のみ' },
];

// 問題ごとにグループ化した回答一覧
const answersWithQuestion = computed<QuestionResult[]>(() => {
  const questions = props.submission.test?.questions ?? [];
  const answers = props.submission.answers ?? [];

  return questions.map((question, index) => {
    const questionAnswers = answers.filter((a) => a.question_id === question.id);
    const selectedIds = questionAnswers.map((a) => a.choice_id).filter((id): id is number => id !== null);
    const isCorrect = questionAnswers.length > 0 ? questionAnswers[0].is_correct : null;

    return {
      id: question.id,
      position: index + 1,
      question,
      is_correct: isCorrect,
      selected_choice_ids: selectedIds,
    };
  });
});

// フィルター適用後の結果
const filteredResults = computed(() => {
  if (filter.value === 'all') return answersWithQuestion.value;
  if (filter.value === 'incorrect') return answersWithQuestion.value.filter(r => r.is_correct === false);
  return answersWithQuestion.value.filter(r => r.is_correct === true);
});

// 正解/不正解/未回答カウント
const correctCount = computed(() => answersWithQuestion.value.filter(r => r.is_correct === true).length);
const incorrectCount = computed(() => answersWithQuestion.value.filter(r => r.is_correct === false).length);
const unansweredCount = computed(() => answersWithQuestion.value.filter(r => r.is_correct === null).length);

// 前回との差分
const scoreDiff = computed(() => {
  if (props.previousScore === null || props.previousScore === undefined || props.submission.score === null) return 0;
  return props.submission.score - props.previousScore;
});
const scoreDiffClass = computed(() => {
  if (scoreDiff.value > 0) return 'text-emerald-600';
  if (scoreDiff.value < 0) return 'text-red-600';
  return 'text-slate-500';
});

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
function choiceClass(choice: Choice, result: QuestionResult): string {
  const isSelected = result.selected_choice_ids.includes(choice.id);
  const isCorrect = choice.is_correct;

  if (isCorrect && isSelected) return 'bg-emerald-50 text-emerald-800';
  if (isCorrect && !isSelected) return 'bg-emerald-50 text-emerald-700 opacity-70';
  if (!isCorrect && isSelected) return 'bg-red-50 text-red-800';
  return 'text-slate-600';
}

</script>
