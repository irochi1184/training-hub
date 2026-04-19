<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">CSV出力</h1>

      <div class="space-y-6">
        <!-- 日報CSV -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="mb-4">
            <h2 class="text-base font-semibold text-gray-800">日報データ</h2>
            <p class="text-sm text-gray-500 mt-1">受講生の日報内容・理解度をCSV形式で出力します</p>
          </div>

          <div class="space-y-4">
            <!-- コホート選択 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">コホート</label>
              <select
                v-model="reportForm.cohort_id"
                class="block w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
              >
                <option value="">すべて</option>
                <option v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                  {{ cohort.name }}
                </option>
              </select>
            </div>

            <!-- 日付範囲 -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">開始日</label>
                <input
                  v-model="reportForm.date_from"
                  type="date"
                  class="block w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">終了日</label>
                <input
                  v-model="reportForm.date_to"
                  type="date"
                  class="block w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                />
              </div>
            </div>

            <!-- ダウンロードリンク -->
            <div>
              <a
                :href="reportDownloadUrl"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors"
                download
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                日報CSV をダウンロード
              </a>
            </div>
          </div>
        </div>

        <!-- テスト結果CSV -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <div class="mb-4">
            <h2 class="text-base font-semibold text-gray-800">テスト結果データ</h2>
            <p class="text-sm text-gray-500 mt-1">受講生のテスト得点・正誤情報をCSV形式で出力します</p>
          </div>

          <div class="space-y-4">
            <!-- テスト選択 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                テスト <span class="text-red-500">*</span>
              </label>
              <select
                v-model="testResultForm.test_id"
                class="block w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
              >
                <option value="" disabled>テストを選択してください</option>
                <option v-for="test in tests" :key="test.id" :value="test.id">
                  {{ test.cohort?.name ? `[${test.cohort.name}] ` : '' }}{{ test.title }}
                </option>
              </select>
            </div>

            <!-- ダウンロードリンク -->
            <div>
              <a
                :href="testResultDownloadUrl"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white rounded transition-colors"
                :class="testResultForm.test_id
                  ? 'bg-green-600 hover:bg-green-700'
                  : 'bg-gray-300 cursor-not-allowed pointer-events-none'"
                :download="!!testResultForm.test_id"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                テスト結果CSV をダウンロード
              </a>
              <p v-if="!testResultForm.test_id" class="mt-1 text-xs text-gray-400">
                テストを選択するとダウンロードできます
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { reactive, computed } from 'vue';
import type { Cohort, Test } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  cohorts: Cohort[];
  tests: Test[];
}>();

// 日報CSV フォーム状態
const reportForm = reactive({
  cohort_id: '',
  date_from: '',
  date_to: '',
});

// テスト結果CSV フォーム状態
const testResultForm = reactive({
  test_id: '',
});

// 日報CSVダウンロードURL（クエリパラメータ付き）
const reportDownloadUrl = computed(() => {
  const params = new URLSearchParams();
  if (reportForm.cohort_id) params.set('cohort_id', String(reportForm.cohort_id));
  if (reportForm.date_from) params.set('date_from', reportForm.date_from);
  if (reportForm.date_to) params.set('date_to', reportForm.date_to);
  const query = params.toString();
  return `/exports/daily-reports${query ? '?' + query : ''}`;
});

// テスト結果CSVダウンロードURL
const testResultDownloadUrl = computed(() => {
  if (!testResultForm.test_id) return '#';
  return `/exports/test-results?test_id=${testResultForm.test_id}`;
});
</script>
