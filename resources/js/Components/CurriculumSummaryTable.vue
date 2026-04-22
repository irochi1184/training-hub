<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100">
      <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
        カリキュラム別サマリ
      </h2>
    </div>

    <div v-if="summaries.length === 0" class="px-6 py-8 text-sm text-slate-400 text-center">
      対象カリキュラムがありません
    </div>

    <table v-else class="min-w-full text-sm">
      <thead class="bg-slate-50 text-xs text-slate-500">
        <tr>
          <th class="px-6 py-2 text-left font-medium">カリキュラム</th>
          <th class="px-4 py-2 text-right font-medium">受講生</th>
          <th class="px-4 py-2 text-right font-medium">平均理解度</th>
          <th class="px-4 py-2 text-right font-medium">平均得点</th>
          <th class="px-4 py-2 text-right font-medium">未解消</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <tr v-for="summary in summaries" :key="summary.id" class="hover:bg-slate-50">
          <td class="px-6 py-3 font-medium text-slate-900">{{ summary.name }}</td>
          <td class="px-4 py-3 text-right text-slate-700">{{ summary.enrollment_count }}</td>
          <td class="px-4 py-3 text-right" :class="understandingClass(summary.avg_understanding)">
            {{ summary.avg_understanding !== null ? summary.avg_understanding.toFixed(1) : '—' }}
          </td>
          <td class="px-4 py-3 text-right" :class="scoreClass(summary.avg_score)">
            {{ summary.avg_score !== null ? summary.avg_score.toFixed(1) : '—' }}
          </td>
          <td class="px-4 py-3 text-right">
            <span
              v-if="summary.unresolved_alert_count > 0"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800"
            >
              {{ summary.unresolved_alert_count }}
            </span>
            <span v-else class="text-slate-400 text-xs">0</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import type { CurriculumSummary } from '@/types';

defineProps<{
  summaries: CurriculumSummary[];
}>();

const LOW_UNDERSTANDING_THRESHOLD = 2.5;
const LOW_SCORE_THRESHOLD = 60;

function understandingClass(avg: number | null): string {
  if (avg === null) return 'text-slate-400';
  return avg < LOW_UNDERSTANDING_THRESHOLD ? 'text-red-600 font-semibold' : 'text-slate-700';
}

function scoreClass(avg: number | null): string {
  if (avg === null) return 'text-slate-400';
  return avg < LOW_SCORE_THRESHOLD ? 'text-red-600 font-semibold' : 'text-slate-700';
}
</script>
