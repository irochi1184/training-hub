<template>
  <AppLayout>
    <div class="max-w-3xl">
      <!-- ヘッダー -->
      <div class="flex items-center gap-4 mb-6">
        <Link
          href="/ai-summaries"
          class="text-sm text-slate-500 hover:text-slate-700 transition-colors"
        >
          ← AI要約一覧
        </Link>
      </div>

      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
        <!-- タイプバッジと対象者 -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
          <span :class="typeBadgeClass">{{ typeLabel }}</span>
          <span class="text-base font-semibold text-slate-800">
            {{ summary.summarizable?.name ?? '—' }}
          </span>
        </div>

        <!-- メタ情報 -->
        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 mb-6 text-sm">
          <div>
            <dt class="text-xs font-medium text-slate-400 mb-0.5">対象期間</dt>
            <dd class="text-slate-700">
              {{ formatDate(summary.week_start) }} 〜 {{ formatDate(summary.week_end) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400 mb-0.5">生成日時</dt>
            <dd class="text-slate-700">{{ formatDateTime(summary.created_at) }}</dd>
          </div>
        </dl>

        <!-- 区切り線 -->
        <hr class="border-slate-100 mb-6" />

        <!-- 要約内容（改行保持） -->
        <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ summary.content }}</div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type { AiSummary } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDate, formatDateTime } from '@/utils/formatDate';

const props = defineProps<{
  summary: AiSummary;
}>();

// 要約タイプの表示ラベルとバッジスタイルを返す
const typeConfig: Record<AiSummary['summary_type'], { label: string; cls: string }> = {
  weekly_student:   { label: '受講生週次', cls: 'bg-indigo-100 text-indigo-800' },
  weekly_class:     { label: 'クラス週次', cls: 'bg-emerald-100 text-emerald-800' },
  risk_explanation: { label: '要注意者説明', cls: 'bg-red-100 text-red-800' },
};

const typeLabel = computed(() => typeConfig[props.summary.summary_type]?.label ?? props.summary.summary_type);
const typeBadgeClass = computed(
  () => `inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${typeConfig[props.summary.summary_type]?.cls ?? 'bg-slate-100 text-slate-600'}`,
);
</script>
