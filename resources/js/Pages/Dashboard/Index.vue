<template>
  <AppLayout>
    <div class="max-w-5xl">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">ダッシュボード</h1>

      <!-- admin向け表示 -->
      <template v-if="user.role === 'admin'">
        <div class="grid grid-cols-3 gap-5 mb-8">
          <StatCard
            label="要注意者（未解消）"
            :value="adminStats.risk_alert_count"
            :alert="adminStats.risk_alert_count > 0"
            unit="名"
            link="/risk-alerts"
          />
          <StatCard
            label="本日の日報提出率"
            :value="adminStats.today_report_rate"
            unit="%"
          />
          <StatCard
            label="テスト受験完了率"
            :value="adminStats.test_completion_rate"
            unit="%"
          />
        </div>

        <!-- 要注意者アラート -->
        <div v-if="adminStats.risk_alert_count > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex items-center gap-2 mb-1">
            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span class="text-sm font-semibold text-red-800">要注意者が {{ adminStats.risk_alert_count }} 名います</span>
          </div>
          <Link href="/risk-alerts" class="text-sm text-red-700 underline hover:no-underline">
            要注意者一覧を確認する
          </Link>
        </div>
      </template>

      <!-- instructor向け表示 -->
      <template v-else-if="user.role === 'instructor'">
        <div class="grid grid-cols-3 gap-5 mb-8">
          <StatCard
            label="担当コホート要注意者"
            :value="instructorStats.risk_alert_count"
            :alert="instructorStats.risk_alert_count > 0"
            unit="名"
            link="/risk-alerts"
          />
          <StatCard
            label="本日の日報提出"
            :value="instructorStats.today_report_count"
            unit="件"
          />
          <StatCard
            label="直近テスト平均点"
            :value="instructorStats.recent_test_avg ?? '—'"
            unit="点"
          />
        </div>

        <!-- 要注意者アラート -->
        <div v-if="instructorStats.risk_alert_count > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
          <div class="flex items-center gap-2 mb-1">
            <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span class="text-sm font-semibold text-red-800">要注意者が {{ instructorStats.risk_alert_count }} 名います</span>
          </div>
          <Link href="/risk-alerts" class="text-sm text-red-700 underline hover:no-underline">
            要注意者一覧を確認する
          </Link>
        </div>
      </template>

      <!-- student向け表示 -->
      <template v-else-if="user.role === 'student'">
        <!-- 未提出警告 -->
        <div v-if="studentStats.has_missing_report" class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <span class="text-sm font-semibold text-yellow-800">本日の日報がまだ提出されていません</span>
          </div>
          <div class="mt-2">
            <Link href="/daily-reports/create" class="text-sm text-yellow-700 underline hover:no-underline">
              日報を提出する
            </Link>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-5">
          <!-- 直近日報 -->
          <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">直近の日報</h2>
            <template v-if="studentStats.latest_report">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">日付</span>
                  <span class="text-sm font-medium">{{ studentStats.latest_report.reported_on }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">理解度</span>
                  <UnderstandingBadge :level="studentStats.latest_report.understanding_level" />
                </div>
              </div>
              <Link
                :href="`/daily-reports/${studentStats.latest_report.id}`"
                class="mt-4 block text-sm text-blue-600 hover:underline"
              >
                詳細を見る
              </Link>
            </template>
            <p v-else class="text-sm text-gray-400">まだ日報がありません</p>
          </div>

          <!-- 直近テスト結果 -->
          <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">直近のテスト結果</h2>
            <template v-if="studentStats.latest_submission">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">テスト</span>
                  <span class="text-sm font-medium truncate max-w-[10rem]">
                    {{ studentStats.latest_submission.test?.title }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">得点</span>
                  <span class="text-sm font-bold text-blue-700">
                    {{ studentStats.latest_submission.score ?? '採点中' }}
                  </span>
                </div>
              </div>
              <Link
                :href="`/submissions/${studentStats.latest_submission.id}`"
                class="mt-4 block text-sm text-blue-600 hover:underline"
              >
                詳細を見る
              </Link>
            </template>
            <p v-else class="text-sm text-gray-400">まだ受験記録がありません</p>
          </div>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type { PageProps, DailyReport, Submission } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import UnderstandingBadge from '@/Components/UnderstandingBadge.vue';
import { defineComponent, h } from 'vue';

// StatCard をインライン定義
const StatCard = defineComponent({
  props: {
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    unit: { type: String, default: '' },
    alert: { type: Boolean, default: false },
    link: { type: String, default: '' },
  },
  setup(props) {
    return () => {
      const inner = h('div', { class: 'bg-white rounded-lg border border-gray-200 p-5' }, [
        h('p', { class: 'text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2' }, props.label),
        h('p', {
          class: [
            'text-3xl font-bold',
            props.alert ? 'text-red-600' : 'text-gray-900',
          ],
        }, [
          String(props.value),
          props.unit ? h('span', { class: 'text-base font-normal text-gray-500 ml-1' }, props.unit) : null,
        ]),
      ]);
      return inner;
    };
  },
});

interface AdminStats {
  risk_alert_count: number;
  today_report_rate: number;
  test_completion_rate: number;
}

interface InstructorStats {
  risk_alert_count: number;
  today_report_count: number;
  recent_test_avg: number | null;
}

interface StudentStats {
  has_missing_report: boolean;
  latest_report: DailyReport | null;
  latest_submission: Submission | null;
}

const props = defineProps<{
  adminStats?: AdminStats;
  instructorStats?: InstructorStats;
  studentStats?: StudentStats;
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

// デフォルト値（バックエンドからデータが来ない場合でも壊れないように）
const adminStats = computed<AdminStats>(() => props.adminStats ?? {
  risk_alert_count: 0,
  today_report_rate: 0,
  test_completion_rate: 0,
});

const instructorStats = computed<InstructorStats>(() => props.instructorStats ?? {
  risk_alert_count: 0,
  today_report_count: 0,
  recent_test_avg: null,
});

const studentStats = computed<StudentStats>(() => props.studentStats ?? {
  has_missing_report: false,
  latest_report: null,
  latest_submission: null,
});
</script>
