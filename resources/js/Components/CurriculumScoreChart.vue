<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-4">
      カリキュラム別テスト平均点
    </h2>

    <div v-if="data.length > 0" class="space-y-3">
      <div
        v-for="item in data"
        :key="item.curriculum_name"
        class="flex items-center gap-3"
      >
        <span
          class="text-xs text-slate-600 w-32 shrink-0 truncate"
          :title="item.curriculum_name"
        >
          {{ item.curriculum_name }}
        </span>

        <template v-if="item.avg_score !== null">
          <div class="flex-1 flex items-center gap-2">
            <div class="flex-1 bg-slate-100 rounded h-4 overflow-hidden">
              <div
                class="h-full rounded transition-colors"
                :class="scoreBarClass(item.avg_score)"
                :style="{ width: `${item.avg_score}%` }"
              />
            </div>
            <span
              class="text-xs font-semibold w-10 text-right"
              :class="scoreTextClass(item.avg_score)"
            >
              {{ item.avg_score }}点
            </span>
          </div>
        </template>

        <span v-else class="flex-1 text-xs text-slate-400">受験データなし</span>
      </div>
    </div>

    <p v-else class="text-sm text-slate-400 text-center py-8">データがありません</p>
  </div>
</template>

<script setup lang="ts">
interface CurriculumScoreItem {
  curriculum_name: string;
  avg_score: number | null;
}

defineProps<{
  data: CurriculumScoreItem[];
}>();

function scoreBarClass(score: number): string {
  if (score >= 80) return 'bg-green-400';
  if (score >= 60) return 'bg-indigo-400';
  return 'bg-red-400';
}

function scoreTextClass(score: number): string {
  if (score >= 80) return 'text-green-600';
  if (score >= 60) return 'text-indigo-600';
  return 'text-red-500';
}
</script>
