<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-sm font-semibold text-slate-700">最新のAI要約</h2>
      <Link href="/ai-summaries" class="text-xs text-indigo-600 hover:underline">
        すべて見る
      </Link>
    </div>

    <!-- 空状態 -->
    <div v-if="summaries.length === 0" class="text-sm text-slate-400">
      まだAI要約がありません
    </div>

    <!-- 要約カード -->
    <div v-else class="space-y-3">
      <Link
        v-for="summary in summaries"
        :key="summary.id"
        :href="`/ai-summaries/${summary.id}`"
        class="block p-4 rounded-lg bg-slate-50 hover:bg-slate-100 transition-colors"
      >
        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
          <!-- 要約タイプバッジ -->
          <span :class="badgeClass(summary.summary_type)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
            {{ typeLabel(summary.summary_type) }}
          </span>
          <!-- 対象者/クラス名 -->
          <span class="text-sm font-medium text-slate-700">
            {{ summary.summarizable?.name ?? '—' }}
          </span>
          <!-- 対象週 -->
          <span class="text-xs text-slate-400">
            {{ formatDate(summary.week_start) }} 〜 {{ formatDate(summary.week_end) }}
          </span>
        </div>
        <!-- 要約内容プレビュー（100文字） -->
        <p class="text-xs text-slate-500 leading-relaxed">
          {{ summary.content.slice(0, 100) }}{{ summary.content.length > 100 ? '…' : '' }}
        </p>
      </Link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { AiSummary } from '@/types';
import { formatDate } from '@/utils/formatDate';

defineProps<{
  summaries: AiSummary[];
}>();

function typeLabel(type: AiSummary['summary_type']): string {
  const map: Record<AiSummary['summary_type'], string> = {
    weekly_student: '受講生週次',
    weekly_class: 'クラス週次',
    risk_explanation: '要注意者説明',
  };
  return map[type] ?? type;
}

function badgeClass(type: AiSummary['summary_type']): string {
  const map: Record<AiSummary['summary_type'], string> = {
    weekly_student: 'bg-indigo-100 text-indigo-800',
    weekly_class: 'bg-emerald-100 text-emerald-800',
    risk_explanation: 'bg-red-100 text-red-800',
  };
  return map[type] ?? 'bg-slate-100 text-slate-600';
}
</script>
