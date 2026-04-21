<template>
  <span
    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
    :class="badgeClass"
  >
    {{ level }}
    <span class="font-normal">{{ levelLabel }}</span>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  level: number;
}>();

const config: Record<number, { classes: string; label: string }> = {
  1: { classes: 'bg-red-100 text-red-800', label: 'まったく理解できない' },
  2: { classes: 'bg-orange-100 text-orange-800', label: 'あまり理解できない' },
  3: { classes: 'bg-yellow-100 text-yellow-800', label: 'なんとなく理解できた' },
  4: { classes: 'bg-lime-100 text-lime-800', label: 'だいたい理解できた' },
  5: { classes: 'bg-emerald-100 text-emerald-800', label: '十分に理解できた' },
};

const badgeClass = computed(() => config[props.level]?.classes ?? 'bg-slate-100 text-slate-600');
const levelLabel = computed(() => config[props.level]?.label ?? '不明');
</script>
