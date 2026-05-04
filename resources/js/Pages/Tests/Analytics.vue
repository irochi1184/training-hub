<template>
  <AppLayout>
    <div class="max-w-5xl">
      <!-- ページヘッダー -->
      <div class="mb-6">
        <Link
          href="/tests"
          class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          テスト一覧に戻る
        </Link>

        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ test.title }}</h1>

        <p class="mt-1 text-sm text-slate-500">
          <span v-if="test.curriculum">{{ test.curriculum.name }}</span>
          <span class="mx-2 text-slate-300">|</span>
          {{ test.questions_count ?? 0 }} 問
        </p>
      </div>

      <!-- サマリーカード -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4 text-center">
          <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-2">受験者数</p>
          <p class="text-3xl font-bold text-slate-900">
            {{ summary.total_submissions }}
            <span class="text-base font-normal text-slate-500 ml-0.5">名</span>
          </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4 text-center">
          <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-2">平均点</p>
          <p class="text-3xl font-bold text-slate-900">
            {{ summary.avg_score != null ? summary.avg_score.toFixed(1) : '—' }}
            <span v-if="summary.avg_score != null" class="text-base font-normal text-slate-500 ml-0.5">点</span>
          </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4 text-center">
          <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-2">最高点</p>
          <p class="text-3xl font-bold text-slate-900">
            {{ summary.max_score ?? '—' }}
            <span v-if="summary.max_score != null" class="text-base font-normal text-slate-500 ml-0.5">点</span>
          </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-4 text-center">
          <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-2">最低点</p>
          <p
            class="text-3xl font-bold"
            :class="isLowScore(summary.min_score) ? 'text-red-600' : 'text-slate-900'"
          >
            {{ summary.min_score ?? '—' }}
            <span v-if="summary.min_score != null" class="text-base font-normal text-slate-500 ml-0.5">点</span>
          </p>
        </div>
      </div>

      <!-- 受験者がいない場合 -->
      <div
        v-if="summary.total_submissions === 0"
        class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 py-16 text-center text-slate-400 mb-8"
      >
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        まだ受験者がいません
      </div>

      <template v-else>
        <!-- 問題別正答率 -->
        <section class="mb-8">
          <h2 class="text-lg font-semibold text-slate-800 mb-4">問題別正答率</h2>

          <div class="space-y-4">
            <div
              v-for="qa in questionAnalytics"
              :key="qa.question_id"
              class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden"
            >
              <!-- 問題ヘッダー -->
              <div
                class="flex items-center justify-between px-5 py-3"
                :class="qa.correct_rate < 50 ? 'bg-red-50' : 'bg-slate-50'"
              >
                <div class="flex items-center gap-2">
                  <span
                    class="text-sm font-semibold"
                    :class="qa.correct_rate < 50 ? 'text-red-800' : 'text-slate-700'"
                  >
                    第{{ qa.position }}問
                  </span>
                  <span
                    class="text-xs"
                    :class="qa.correct_rate < 50 ? 'text-red-600' : 'text-slate-400'"
                  >
                    ({{ qa.score }}点)
                  </span>
                  <span
                    v-if="qa.correct_rate < 50"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-700"
                  >
                    要注意
                  </span>
                </div>
                <span
                  class="text-sm font-bold"
                  :class="qa.correct_rate < 50 ? 'text-red-700' : 'text-slate-700'"
                >
                  正答率 {{ qa.correct_rate }}%
                </span>
              </div>

              <!-- 問題本文・選択肢グラフ -->
              <div class="px-5 py-4">
                <p class="text-sm text-slate-800 mb-4 leading-relaxed">{{ qa.body }}</p>

                <div class="space-y-2.5">
                  <div
                    v-for="cs in qa.choice_stats"
                    :key="cs.choice_id"
                    class="flex items-center gap-3"
                  >
                    <!-- 棒グラフ -->
                    <div class="w-32 sm:w-48 shrink-0">
                      <div class="h-5 rounded overflow-hidden bg-slate-100">
                        <div
                          class="h-full rounded transition-all"
                          :class="choiceBarColor(cs, qa)"
                          :style="{ width: barWidth(cs, qa) + '%' }"
                        />
                      </div>
                    </div>

                    <!-- 選択肢テキスト -->
                    <div class="flex items-center gap-2 min-w-0">
                      <span class="text-sm text-slate-700 truncate">{{ cs.body }}</span>
                      <span
                        v-if="cs.is_correct"
                        class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-700"
                      >
                        正解
                      </span>
                    </div>

                    <!-- 割合 -->
                    <span class="ml-auto shrink-0 text-sm text-slate-500 tabular-nums">{{ cs.rate }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- 受験者一覧 -->
        <section>
          <h2 class="text-lg font-semibold text-slate-800 mb-4">受験者一覧</h2>

          <DataTable
            :empty="submissions.length === 0"
            empty-message="受験者がいません"
            :col-span="4"
          >
            <template #head>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">受験者名</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">提出日時</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">得点</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">正答率</th>
            </template>

            <template #body>
              <tr
                v-for="sub in submissions"
                :key="sub.submission_id"
                class="hover:bg-slate-50 transition-colors"
              >
                <td class="px-4 py-3 text-sm">
                  <Link
                    :href="`/submissions/${sub.submission_id}`"
                    class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline"
                  >
                    {{ sub.user_name }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-sm text-slate-500">
                  {{ sub.submitted_at ? formatDateTime(sub.submitted_at) : '—' }}
                </td>
                <td class="px-4 py-3 text-sm font-semibold tabular-nums"
                  :class="isLowSubmissionScore(sub.score) ? 'text-red-600' : 'text-slate-900'"
                >
                  {{ sub.score ?? '—' }} 点
                </td>
                <td class="px-4 py-3 text-sm tabular-nums"
                  :class="isLowSubmissionScore(sub.score) ? 'text-red-500' : 'text-slate-600'"
                >
                  {{ submissionRate(sub.score) }}
                </td>
              </tr>
            </template>
          </DataTable>
        </section>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { Test, QuestionAnalytics, AnalyticsSummary, ChoiceStat } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDateTime } from '@/utils/formatDate';

interface AnalyticsSubmission {
  submission_id: number;
  user_name: string;
  score: number | null;
  submitted_at: string | null;
}

const props = defineProps<{
  test: Test;
  questionAnalytics: QuestionAnalytics[];
  summary: AnalyticsSummary;
  submissions: AnalyticsSubmission[];
}>();

// 最低点が満点の60%未満なら赤表示
function isLowScore(score: number | null): boolean {
  if (score == null) return false;
  return score < props.summary.total_points * 0.6;
}

// 受験者のスコアが満点の半分未満なら赤表示
function isLowSubmissionScore(score: number | null): boolean {
  if (score == null) return false;
  return score < props.summary.total_points / 2;
}

// スコアから正答率文字列を生成
function submissionRate(score: number | null): string {
  if (score == null || props.summary.total_points === 0) return '—';
  const rate = Math.round((score / props.summary.total_points) * 100);
  return `${rate}%`;
}

// 棒グラフの幅を算出。最も多く選ばれた選択肢を100%基準にする。
function barWidth(cs: ChoiceStat, qa: QuestionAnalytics): number {
  const maxRate = Math.max(...qa.choice_stats.map((c) => c.rate), 1);
  return maxRate === 0 ? 0 : Math.round((cs.rate / maxRate) * 100);
}

// 最も多く選ばれた不正解の選択肢を特定
function isMostSelectedWrong(cs: ChoiceStat, qa: QuestionAnalytics): boolean {
  if (cs.is_correct) return false;
  const wrongChoices = qa.choice_stats.filter((c) => !c.is_correct);
  if (wrongChoices.length === 0) return false;
  const maxCount = Math.max(...wrongChoices.map((c) => c.count));
  return cs.count === maxCount && maxCount > 0;
}

// 選択肢の棒グラフ色を決定
function choiceBarColor(cs: ChoiceStat, qa: QuestionAnalytics): string {
  if (cs.is_correct) return 'bg-emerald-400';
  if (isMostSelectedWrong(cs, qa)) return 'bg-red-300';
  return 'bg-slate-200';
}
</script>
