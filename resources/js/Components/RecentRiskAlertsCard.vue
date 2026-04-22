<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
        直近の未解消アラート
      </h2>
      <Link href="/risk-alerts" class="text-xs text-indigo-600 hover:text-indigo-800">
        一覧 →
      </Link>
    </div>

    <ul v-if="alerts.length > 0" class="divide-y divide-slate-100">
      <li
        v-for="alert in alerts"
        :key="alert.id"
        class="py-3 flex items-start gap-3"
      >
        <span
          class="shrink-0 mt-0.5 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold"
          :class="reasonClass(alert.reason)"
        >
          {{ reasonLabel(alert.reason) }}
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium text-slate-900 truncate">
            {{ alert.user_name ?? '不明な受講生' }}
          </p>
          <p class="text-xs text-slate-500 truncate">
            {{ alert.curriculum_name ?? '-' }} ・ {{ alert.created_at ?? '' }}
          </p>
        </div>
      </li>
    </ul>

    <p v-else class="text-sm text-slate-400">未解消のアラートはありません</p>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { DashboardRiskAlert } from '@/types';

defineProps<{
  alerts: DashboardRiskAlert[];
}>();

const REASON_LABELS: Record<DashboardRiskAlert['reason'], string> = {
  low_understanding: '理解度低下',
  report_missing: '日報未提出',
  low_score: '得点低下',
};

const REASON_CLASSES: Record<DashboardRiskAlert['reason'], string> = {
  low_understanding: 'bg-orange-100 text-orange-800',
  report_missing: 'bg-yellow-100 text-yellow-800',
  low_score: 'bg-red-100 text-red-800',
};

function reasonLabel(reason: DashboardRiskAlert['reason']): string {
  return REASON_LABELS[reason] ?? reason;
}

function reasonClass(reason: DashboardRiskAlert['reason']): string {
  return REASON_CLASSES[reason] ?? 'bg-slate-100 text-slate-800';
}
</script>
