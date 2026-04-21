<template>
  <AppLayout>
    <div class="max-w-3xl">
      <!-- 戻るリンク -->
      <Link
        :href="backUrl"
        class="text-sm text-slate-500 hover:text-slate-800 flex items-center gap-1 mb-4"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        戻る
      </Link>

      <!-- 日報内容 -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
        <!-- ヘッダー情報 -->
        <div class="flex items-start justify-between mb-5 pb-5 border-b border-slate-100">
          <div>
            <h1 class="text-lg font-bold text-slate-900 mb-1">
              {{ formatDate(report.reported_on) }} の日報
            </h1>
            <p class="text-sm text-slate-500">
              提出者: <span class="font-medium text-slate-700">{{ report.user?.name ?? '—' }}</span>
              <span class="mx-2 text-slate-300">|</span>
              {{ report.cohort?.name ?? '—' }}
            </p>
          </div>
          <UnderstandingBadge :level="report.understanding_level" />
        </div>

        <!-- 学習内容 -->
        <div class="mb-5">
          <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">学習内容</h2>
          <p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">{{ report.content }}</p>
        </div>

        <!-- 感想・気づき -->
        <div v-if="report.impression">
          <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">感想・気づき</h2>
          <p class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">{{ report.impression }}</p>
        </div>
        <div v-else>
          <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">感想・気づき</h2>
          <p class="text-sm text-slate-400">（記入なし）</p>
        </div>
      </div>

      <!-- コメントセクション -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">
          コメント
          <span class="text-xs font-normal text-slate-400 ml-1">{{ report.comments?.length ?? 0 }} 件</span>
        </h2>

        <!-- コメント一覧 -->
        <div class="mb-6">
          <CommentList
            :comments="report.comments ?? []"
            :current-user-id="currentUserId"
            :can-delete="canComment"
            :delete-url-prefix="`/daily-reports/${report.id}/comments/`"
          />
        </div>

        <!-- コメント投稿フォーム（admin/instructor のみ） -->
        <div v-if="canComment" class="border-t border-slate-100 pt-5">
          <CommentForm :post-url="`/daily-reports/${report.id}/comments`" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { DailyReport, PageProps } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import UnderstandingBadge from '@/Components/UnderstandingBadge.vue';
import CommentList from '@/Components/CommentList.vue';
import CommentForm from '@/Components/CommentForm.vue';
import { formatDate } from '@/utils/formatDate';

const props = defineProps<{
  report: DailyReport;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const currentUserId = computed(() => user.value.id);
const canComment = computed(() =>
  user.value.role === 'admin' || user.value.role === 'instructor',
);

const backUrl = computed(() => {
  if (user.value.role === 'student') return '/dashboard';
  return '/daily-reports';
});
</script>
