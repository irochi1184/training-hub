<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-4">
      日報提出率（直近7日）
    </h2>

    <div v-if="data.length > 0" class="flex items-end gap-2 h-40">
      <div
        v-for="item in data"
        :key="item.date"
        class="flex-1 flex flex-col items-center gap-1"
      >
        <!-- rate値ラベル -->
        <span class="text-[10px] font-semibold" :class="rateTextClass(item.rate)">
          {{ item.rate }}%
        </span>
        <!-- 棒グラフコンテナ: 高さ128px固定 -->
        <div class="w-full flex flex-col justify-end" style="height: 128px">
          <div
            class="w-full rounded-t transition-colors"
            :class="rateBarClass(item.rate)"
            :style="{ height: `${item.rate}%` }"
            :title="`${formatDay(item.date)}: ${item.rate}%`"
          />
        </div>
        <!-- 日付ラベル -->
        <span class="text-[10px] text-slate-500">{{ formatDay(item.date) }}</span>
      </div>
    </div>

    <p v-else class="text-sm text-slate-400 text-center py-8">データがありません</p>
  </div>
</template>

<script setup lang="ts">
interface ReportRateItem {
  date: string;
  rate: number;
}

defineProps<{
  data: ReportRateItem[];
}>();

function rateBarClass(rate: number): string {
  if (rate >= 80) return 'bg-green-400';
  if (rate >= 50) return 'bg-yellow-400';
  return 'bg-red-400';
}

function rateTextClass(rate: number): string {
  if (rate >= 80) return 'text-green-600';
  if (rate >= 50) return 'text-yellow-600';
  return 'text-red-500';
}

function formatDay(date: string): string {
  const d = new Date(date);
  const m = d.getMonth() + 1;
  const day = d.getDate();
  return `${m}/${day}`;
}
</script>
