<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest">
        理解度の推移（直近{{ trend.length }}日）
      </h2>
    </div>

    <div v-if="hasAnyReport" class="flex items-end gap-3 h-32">
      <div
        v-for="(item, index) in trend"
        :key="index"
        class="flex-1 flex flex-col items-center gap-1.5"
      >
        <div class="w-full flex flex-col justify-end h-24">
          <div
            v-if="item.level !== null"
            class="w-full rounded-t transition-colors"
            :class="understandingBarClass(item.level)"
            :style="{ height: barHeight(item.level) }"
            :title="`${formatDay(item.date)}: ${item.level}`"
          />
          <div
            v-else
            class="w-full rounded-t bg-slate-100 border border-dashed border-slate-300"
            style="height: 4px"
            :title="`${formatDay(item.date)}: 未提出`"
          />
        </div>
        <span class="text-[10px] text-slate-500">{{ formatDay(item.date) }}</span>
        <span class="text-[11px] font-semibold" :class="item.level !== null ? 'text-slate-700' : 'text-slate-300'">
          {{ item.level ?? '—' }}
        </span>
      </div>
    </div>

    <p v-else class="text-sm text-slate-400 text-center py-8">
      この期間に提出された日報がありません
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { UnderstandingTrendItem } from '@/types';
import { understandingBarClass } from '@/utils/understandingLevel';

const props = defineProps<{
  trend: UnderstandingTrendItem[];
}>();

const MAX_LEVEL = 5;

const hasAnyReport = computed(() => props.trend.some((item) => item.level !== null));

function barHeight(level: number): string {
  return `${(level / MAX_LEVEL) * 100}%`;
}

function formatDay(date: string): string {
  const d = new Date(date);
  return `${d.getMonth() + 1}/${d.getDate()}`;
}
</script>
