<template>
  <AppLayout>
    <div class="max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">お知らせ</h1>
        <Link
          v-if="canCreate"
          href="/announcements-create"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          お知らせを作成
        </Link>
      </div>

      <!-- 一覧 -->
      <div v-if="announcements.data.length > 0" class="space-y-3">
        <Link
          v-for="item in announcements.data"
          :key="item.id"
          :href="`/announcements/${item.id}`"
          class="block bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-5 hover:ring-indigo-200 transition-all"
          :class="!isRead(item.id) ? 'border-l-4 border-l-indigo-500' : ''"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span
                  v-if="item.priority === 'important'"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
                >
                  重要
                </span>
                <span
                  v-if="!isRead(item.id)"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700"
                >
                  未読
                </span>
                <span v-if="canCreate" class="text-xs text-slate-400">
                  {{ targetLabel(item) }}
                </span>
              </div>
              <h2 class="text-sm font-semibold text-slate-900 truncate" :class="!isRead(item.id) ? 'font-bold' : ''">
                {{ item.title }}
              </h2>
              <p class="mt-1 text-xs text-slate-500">
                {{ item.creator?.name }} ・ {{ formatDate(item.published_at || item.created_at) }}
              </p>
            </div>
            <svg class="w-4 h-4 text-slate-400 shrink-0 mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </Link>
      </div>

      <!-- 空状態 -->
      <div v-else class="text-center py-16 text-slate-400">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <p class="text-sm">お知らせはありません</p>
      </div>

      <Pagination :data="announcements" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { Announcement, PageProps, PaginatedData } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps<{
  announcements: PaginatedData<Announcement>;
  readIds: number[];
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const canCreate = computed(() => user.value.role === 'admin' || user.value.role === 'instructor');

function isRead(id: number): boolean {
  return props.readIds.includes(id);
}

function targetLabel(item: Announcement): string {
  switch (item.target_type) {
    case 'all': return '全員';
    case 'curriculum': return 'カリキュラム指定';
    case 'user': return '個別指定';
    default: return '';
  }
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('ja-JP', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>
