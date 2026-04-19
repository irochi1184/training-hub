<template>
  <span
    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
    :class="colorClass"
  >
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// color: 'green' | 'yellow' | 'red' | 'blue' | 'gray' | 'orange'
const props = defineProps<{
  color?: 'green' | 'yellow' | 'red' | 'blue' | 'gray' | 'orange';
  label?: string;
}>();

const colorClass = computed(() => {
  const map: Record<string, string> = {
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-100 text-red-800',
    blue: 'bg-blue-100 text-blue-800',
    gray: 'bg-gray-100 text-gray-600',
    orange: 'bg-orange-100 text-orange-800',
  };
  return map[props.color ?? 'gray'] ?? map['gray'];
});
</script>
