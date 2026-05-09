<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-4">成績推移</h2>

    <div v-if="data.length === 0" class="flex items-center justify-center h-48 text-sm text-slate-400">
      受験データがありません
    </div>

    <div v-else>
      <!-- SVG 折れ線グラフ -->
      <div class="relative">
        <svg
          :viewBox="`0 0 ${svgWidth} ${svgHeight}`"
          class="w-full"
          preserveAspectRatio="xMidYMid meet"
        >
          <!-- Y軸グリッド線 -->
          <line
            v-for="y in yGridLines"
            :key="'grid-' + y.value"
            :x1="padding.left"
            :y1="y.pos"
            :x2="svgWidth - padding.right"
            :y2="y.pos"
            stroke="#e2e8f0"
            stroke-width="1"
            stroke-dasharray="4 4"
          />

          <!-- Y軸ラベル -->
          <text
            v-for="y in yGridLines"
            :key="'label-' + y.value"
            :x="padding.left - 6"
            :y="y.pos + 4"
            text-anchor="end"
            class="fill-slate-400"
            font-size="10"
          >
            {{ y.value }}
          </text>

          <!-- 得点率の折れ線 -->
          <polyline
            :points="linePoints"
            fill="none"
            stroke="#6366f1"
            stroke-width="2.5"
            stroke-linejoin="round"
            stroke-linecap="round"
          />

          <!-- データポイント -->
          <g v-for="(point, i) in points" :key="'point-' + i">
            <circle
              :cx="point.x"
              :cy="point.y"
              r="4.5"
              :fill="scoreColor(point.rate)"
              stroke="white"
              stroke-width="2"
            />
          </g>

          <!-- X軸ラベル（テスト名短縮） -->
          <text
            v-for="(point, i) in points"
            :key="'xlabel-' + i"
            :x="point.x"
            :y="svgHeight - 4"
            text-anchor="middle"
            class="fill-slate-400"
            font-size="9"
          >
            {{ truncate(point.label, 6) }}
          </text>
        </svg>
      </div>

      <!-- 詳細リスト -->
      <div class="mt-4 space-y-1.5 max-h-40 overflow-y-auto">
        <div
          v-for="(item, i) in data"
          :key="i"
          class="flex items-center gap-2 text-xs"
        >
          <span
            class="w-2.5 h-2.5 rounded-full shrink-0"
            :class="dotClass(item)"
          />
          <span class="text-slate-500 w-20 shrink-0">{{ formatShortDate(item.submitted_at) }}</span>
          <span class="text-slate-700 truncate flex-1" :title="item.test_title">{{ item.test_title }}</span>
          <span class="font-medium tabular-nums" :class="scoreTextClass(item)">
            {{ item.score }}<span class="text-slate-400">/{{ item.total_points }}</span>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { ScoreTrendItem } from '@/types';

const props = defineProps<{
  data: ScoreTrendItem[];
}>();

const svgWidth = 400;
const svgHeight = 200;
const padding = { top: 16, right: 16, bottom: 24, left: 36 };

const chartWidth = svgWidth - padding.left - padding.right;
const chartHeight = svgHeight - padding.top - padding.bottom;

const yGridLines = computed(() => {
  const steps = [0, 25, 50, 75, 100];
  return steps.map(v => ({
    value: v,
    pos: padding.top + chartHeight - (v / 100) * chartHeight,
  }));
});

const points = computed(() => {
  if (props.data.length === 0) return [];

  const step = props.data.length === 1 ? 0 : chartWidth / (props.data.length - 1);

  return props.data.map((item, i) => {
    const rate = item.total_points > 0
      ? (item.score / item.total_points) * 100
      : 0;
    const x = padding.left + (props.data.length === 1 ? chartWidth / 2 : i * step);
    const y = padding.top + chartHeight - (rate / 100) * chartHeight;

    return { x, y, rate, label: item.test_title };
  });
});

const linePoints = computed(() =>
  points.value.map(p => `${p.x},${p.y}`).join(' ')
);

function scoreColor(rate: number): string {
  if (rate >= 80) return '#34d399';
  if (rate >= 60) return '#6366f1';
  return '#f87171';
}

function truncate(text: string, max: number): string {
  return text.length > max ? text.slice(0, max) + '…' : text;
}

function formatShortDate(dateStr: string): string {
  const d = new Date(dateStr);
  return `${d.getMonth() + 1}/${d.getDate()}`;
}

function dotClass(item: ScoreTrendItem): string {
  const rate = item.total_points > 0 ? (item.score / item.total_points) * 100 : 0;
  if (rate >= 80) return 'bg-emerald-400';
  if (rate >= 60) return 'bg-indigo-400';
  return 'bg-red-400';
}

function scoreTextClass(item: ScoreTrendItem): string {
  const rate = item.total_points > 0 ? (item.score / item.total_points) * 100 : 0;
  if (rate >= 80) return 'text-emerald-600';
  if (rate >= 60) return 'text-indigo-600';
  return 'text-red-500';
}
</script>
