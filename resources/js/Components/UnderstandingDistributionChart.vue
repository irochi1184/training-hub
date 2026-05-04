<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-4">
      理解度分布（直近7日）
    </h2>

    <div v-if="data.length > 0" class="space-y-3">
      <div
        v-for="row in data"
        :key="row.curriculum_name"
        class="flex items-center gap-3"
      >
        <span class="text-xs text-slate-600 w-28 shrink-0 truncate" :title="row.curriculum_name">
          {{ row.curriculum_name }}
        </span>
        <div class="flex-1 flex h-5 rounded overflow-hidden bg-slate-100">
          <template v-if="total(row) > 0">
            <!-- レベル1-2: 赤 -->
            <div
              v-if="lowCount(row) > 0"
              class="bg-red-400 h-full"
              :style="{ width: pct(lowCount(row), total(row)) }"
              :title="`レベル1-2: ${lowCount(row)}件`"
            />
            <!-- レベル3: 黄 -->
            <div
              v-if="row.levels[2] > 0"
              class="bg-yellow-400 h-full"
              :style="{ width: pct(row.levels[2], total(row)) }"
              :title="`レベル3: ${row.levels[2]}件`"
            />
            <!-- レベル4-5: 緑 -->
            <div
              v-if="highCount(row) > 0"
              class="bg-green-400 h-full"
              :style="{ width: pct(highCount(row), total(row)) }"
              :title="`レベル4-5: ${highCount(row)}件`"
            />
          </template>
          <div v-else class="w-full h-full bg-slate-100" />
        </div>
        <span class="text-xs text-slate-400 w-8 text-right">{{ total(row) }}</span>
      </div>

      <!-- 凡例 -->
      <div class="flex items-center gap-4 mt-4 pt-3 border-t border-slate-100">
        <div class="flex items-center gap-1.5">
          <span class="inline-block w-3 h-3 rounded-sm bg-red-400" />
          <span class="text-xs text-slate-500">レベル1-2</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="inline-block w-3 h-3 rounded-sm bg-yellow-400" />
          <span class="text-xs text-slate-500">レベル3</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="inline-block w-3 h-3 rounded-sm bg-green-400" />
          <span class="text-xs text-slate-500">レベル4-5</span>
        </div>
      </div>
    </div>

    <p v-else class="text-sm text-slate-400 text-center py-8">データがありません</p>
  </div>
</template>

<script setup lang="ts">
import type { UnderstandingDistribution } from '@/types';

defineProps<{
  data: UnderstandingDistribution[];
}>();

function total(row: UnderstandingDistribution): number {
  return row.levels.reduce((sum, n) => sum + n, 0);
}

function lowCount(row: UnderstandingDistribution): number {
  return row.levels[0] + row.levels[1];
}

function highCount(row: UnderstandingDistribution): number {
  return row.levels[3] + row.levels[4];
}

function pct(count: number, tot: number): string {
  if (tot === 0) return '0%';
  return `${Math.round((count / tot) * 100)}%`;
}
</script>
