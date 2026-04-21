<template>
  <div v-if="links.length > 3" class="flex items-center justify-between mt-4">
    <p class="text-sm text-slate-500">
      全 {{ total }} 件中 {{ from }}〜{{ to }} 件を表示
    </p>
    <nav class="flex items-center gap-1">
      <template v-for="link in links" :key="link.label">
        <span
          v-if="link.url === null"
          class="px-3 py-1.5 text-sm rounded-lg text-slate-300 cursor-default"
          v-html="link.label"
        />
        <Link
          v-else
          :href="link.url"
          class="px-3 py-1.5 text-sm rounded-lg transition-colors"
          :class="link.active
            ? 'bg-indigo-600 text-white font-medium shadow-sm'
            : 'text-slate-600 hover:bg-slate-100'"
          v-html="link.label"
          preserve-scroll
        />
      </template>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { PaginatedData } from '@/types';

const props = defineProps<{
  data: PaginatedData<unknown>;
}>();

const links = computed(() => props.data.links);
const total = computed(() => props.data.total);
const perPage = computed(() => props.data.per_page);
const currentPage = computed(() => props.data.current_page);

const from = computed(() => (currentPage.value - 1) * perPage.value + 1);
const to = computed(() => Math.min(currentPage.value * perPage.value, total.value));
</script>
