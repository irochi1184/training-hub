<template>
  <AppLayout>
    <div class="max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">AI要約</h1>
        <!-- admin / instructor のみ手動生成ボタンを表示 -->
        <button
          v-if="canGenerate"
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
          @click="showGenerateModal = true"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          要約を生成
        </button>
      </div>

      <!-- フィルター -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-5 mb-5">
        <div class="flex flex-wrap items-center gap-4">
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-700 shrink-0">種別:</label>
            <select
              :value="filters.summary_type ?? ''"
              class="text-sm border border-slate-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
              @change="applyFilter({ summary_type: ($event.target as HTMLSelectElement).value || undefined })"
            >
              <option value="">すべて</option>
              <option v-for="t in summaryTypes" :key="t.value" :value="t.value">
                {{ t.label }}
              </option>
            </select>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-700 shrink-0">週:</label>
            <input
              type="week"
              :value="weekInputValue"
              class="text-sm border border-slate-300 rounded px-3 py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
              @change="onWeekChange(($event.target as HTMLInputElement).value)"
            />
          </div>
          <button
            v-if="hasActiveFilter"
            type="button"
            class="text-sm text-slate-500 hover:text-slate-700 underline"
            @click="clearFilters"
          >
            絞り込み解除
          </button>
        </div>
      </div>

      <!-- 空状態 -->
      <div
        v-if="summaries.data.length === 0"
        class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-16 text-center"
      >
        <p class="text-slate-400 text-sm">
          {{ hasActiveFilter ? '条件に一致する要約がありません' : 'まだAI要約がありません' }}
        </p>
      </div>

      <!-- 要約カード一覧 -->
      <div v-else class="space-y-3 mb-4">
        <div
          v-for="summary in summaries.data"
          :key="summary.id"
          class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-5"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <!-- 要約タイプバッジ -->
                <SummaryTypeBadge :type="summary.summary_type" />
                <!-- 対象者/クラス名 -->
                <span class="text-sm font-medium text-slate-700">
                  {{ summary.summarizable?.name ?? '—' }}
                </span>
                <!-- 対象週 -->
                <span class="text-xs text-slate-400">
                  {{ formatDate(summary.week_start) }} 〜 {{ formatDate(summary.week_end) }}
                </span>
              </div>
              <!-- 要約内容プレビュー（100文字） -->
              <p class="text-sm text-slate-600 leading-relaxed line-clamp-2">
                {{ summary.content.slice(0, 100) }}{{ summary.content.length > 100 ? '…' : '' }}
              </p>
            </div>
            <Link
              :href="`/ai-summaries/${summary.id}`"
              class="shrink-0 text-sm text-indigo-600 hover:underline"
            >
              詳細
            </Link>
          </div>
        </div>
      </div>

      <Pagination :data="summaries" />
    </div>

    <!-- 手動生成モーダル -->
    <Teleport to="body">
      <div
        v-if="showGenerateModal"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="showGenerateModal = false"
      >
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
          <h2 class="text-lg font-semibold text-slate-900 mb-4">AI要約を生成</h2>
          <form @submit.prevent="submitGenerate">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">種別</label>
                <select
                  v-model="generateForm.summary_type"
                  class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                  required
                >
                  <option value="">選択してください</option>
                  <option v-for="t in summaryTypes" :key="t.value" :value="t.value">
                    {{ t.label }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">対象ID</label>
                <input
                  v-model.number="generateForm.target_id"
                  type="number"
                  min="1"
                  class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="受講生IDまたはカリキュラムID"
                  required
                />
              </div>
              <div v-if="generateForm.summary_type !== 'risk_explanation'">
                <label class="block text-sm font-medium text-slate-700 mb-1">対象週の開始日</label>
                <input
                  v-model="generateForm.week_start"
                  type="date"
                  class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button
                type="button"
                class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
                @click="showGenerateModal = false"
              >
                キャンセル
              </button>
              <button
                type="submit"
                :disabled="generating"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50"
              >
                {{ generating ? '生成中…' : '生成する' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, defineComponent, h } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { AiSummary, PaginatedData, PageProps } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/utils/formatDate';

// 要約タイプバッジ（ファイル内インライン定義）
const SummaryTypeBadge = defineComponent({
  props: {
    type: { type: String as () => AiSummary['summary_type'], required: true },
  },
  setup(props) {
    const config: Record<AiSummary['summary_type'], { label: string; cls: string }> = {
      weekly_student: { label: '受講生週次', cls: 'bg-indigo-100 text-indigo-800' },
      weekly_class:   { label: 'クラス週次', cls: 'bg-emerald-100 text-emerald-800' },
      risk_explanation: { label: '要注意者説明', cls: 'bg-red-100 text-red-800' },
    };
    return () => {
      const c = config[props.type] ?? { label: props.type, cls: 'bg-slate-100 text-slate-600' };
      return h(
        'span',
        { class: `inline-flex items-center px-2 py-0.5 rounded text-xs font-medium shrink-0 ${c.cls}` },
        c.label,
      );
    };
  },
});

type SummaryTypeOption = { value: string; label: string };

const props = defineProps<{
  summaries: PaginatedData<AiSummary>;
  summaryTypes: SummaryTypeOption[];
  filters: {
    summary_type?: string;
    week_start?: string;
  };
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);
const canGenerate = computed(() => user.value.role === 'admin' || user.value.role === 'instructor');

const hasActiveFilter = computed(
  () => Boolean(props.filters.summary_type) || Boolean(props.filters.week_start),
);

// week_start (YYYY-MM-DD) を <input type="week"> の YYYY-Www 形式に変換
const weekInputValue = computed<string>(() => {
  if (!props.filters.week_start) return '';
  const d = new Date(props.filters.week_start);
  if (isNaN(d.getTime())) return '';
  // ISO週番号を求める
  const jan4 = new Date(d.getFullYear(), 0, 4);
  const dayOfWeek = jan4.getDay() || 7;
  const weekStart = new Date(jan4);
  weekStart.setDate(jan4.getDate() - dayOfWeek + 1);
  const diff = d.getTime() - weekStart.getTime();
  const week = Math.floor(diff / (7 * 24 * 60 * 60 * 1000)) + 1;
  return `${d.getFullYear()}-W${String(week).padStart(2, '0')}`;
});

type FilterPatch = { summary_type?: string; week_start?: string };

function applyFilter(patch: FilterPatch): void {
  const query: Record<string, string> = {};
  const nextType = 'summary_type' in patch ? patch.summary_type : props.filters.summary_type;
  const nextWeek = 'week_start' in patch ? patch.week_start : props.filters.week_start;
  if (nextType) query.summary_type = nextType;
  if (nextWeek) query.week_start = nextWeek;
  router.get('/ai-summaries', query, { preserveState: true, replace: true });
}

// YYYY-Www を YYYY-MM-DD (月曜日) に変換してフィルターに渡す
function onWeekChange(weekValue: string): void {
  if (!weekValue) {
    applyFilter({ week_start: undefined });
    return;
  }
  // weekValue: "2026-W20" → 月曜日の日付を計算
  const [yearStr, weekStr] = weekValue.split('-W');
  const year = parseInt(yearStr);
  const week = parseInt(weekStr);
  // ISO 8601: 週1は1月4日を含む週
  const jan4 = new Date(year, 0, 4);
  const dayOfWeek = jan4.getDay() || 7;
  const monday = new Date(jan4);
  monday.setDate(jan4.getDate() - dayOfWeek + 1 + (week - 1) * 7);
  const isoDate = monday.toISOString().slice(0, 10);
  applyFilter({ week_start: isoDate });
}

function clearFilters(): void {
  router.get('/ai-summaries', {}, { preserveState: true, replace: true });
}

// 手動生成モーダル
const showGenerateModal = ref(false);
const generating = ref(false);
const generateForm = ref({
  summary_type: '',
  target_id: null as number | null,
  week_start: '',
});

function submitGenerate(): void {
  if (!generateForm.value.summary_type || !generateForm.value.target_id) return;
  generating.value = true;
  router.post(
    '/ai-summaries/generate',
    {
      summary_type: generateForm.value.summary_type,
      target_id: generateForm.value.target_id,
      week_start: generateForm.value.week_start || undefined,
    },
    {
      onFinish: () => {
        generating.value = false;
        showGenerateModal.value = false;
        generateForm.value = { summary_type: '', target_id: null, week_start: '' };
      },
    },
  );
}
</script>
