<template>
  <component
    :is="link ? Link : 'div'"
    :href="link || undefined"
    class="block bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 transition-colors"
    :class="link ? 'hover:bg-slate-50' : ''"
  >
    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-3">
      {{ label }}
    </p>
    <p
      class="text-3xl font-bold"
      :class="alert ? 'text-red-600' : 'text-slate-900'"
    >
      {{ value }}
      <span v-if="unit" class="text-base font-normal text-slate-500 ml-1">{{ unit }}</span>
    </p>
  </component>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
  label: string;
  value: number | string;
  unit?: string;
  alert?: boolean;
  link?: string;
}>();
</script>
