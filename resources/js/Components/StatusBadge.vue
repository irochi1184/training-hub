<template>
  <span
    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
    :class="colorClass"
  >
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  color?: 'green' | 'yellow' | 'red' | 'blue' | 'gray' | 'orange';
  label?: string;
}>();

const colorClass = computed(() => {
  const map: Record<string, string> = {
    green: 'bg-emerald-100 text-emerald-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-100 text-red-800',
    blue: 'bg-indigo-100 text-indigo-800',
    gray: 'bg-slate-100 text-slate-600',
    orange: 'bg-orange-100 text-orange-800',
  };
  return map[props.color ?? 'gray'] ?? map['gray'];
});
</script>
