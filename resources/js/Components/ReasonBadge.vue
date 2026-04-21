<template>
  <span
    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
    :class="badgeClass"
  >
    {{ reasonLabel }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { RiskAlert } from '@/types';

const props = defineProps<{
  reason: RiskAlert['reason'];
}>();

const config: Record<RiskAlert['reason'], { label: string; classes: string }> = {
  low_understanding: {
    label: '理解度低下',
    classes: 'bg-orange-100 text-orange-800',
  },
  report_missing: {
    label: '日報未提出',
    classes: 'bg-red-100 text-red-800',
  },
  low_score: {
    label: 'テスト低得点',
    classes: 'bg-purple-100 text-purple-800',
  },
};

const reasonLabel = computed(() => config[props.reason]?.label ?? props.reason);
const badgeClass = computed(() => config[props.reason]?.classes ?? 'bg-slate-100 text-slate-600');
</script>
