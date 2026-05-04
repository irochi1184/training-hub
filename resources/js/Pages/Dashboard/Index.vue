<template>
  <AppLayout>
    <div class="max-w-6xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">ダッシュボード</h1>

      <!-- admin向け表示 -->
      <template v-if="user.role === 'admin'">
        <div class="grid grid-cols-3 gap-5 mb-6">
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

        <div class="grid grid-cols-3 gap-5 mb-6">
          <div class="col-span-2">
            <CurriculumSummaryTable :summaries="curriculumSummaries" />
          </div>
          <RecentRiskAlertsCard :alerts="recentRiskAlerts" />
        </div>

        <!-- グラフセクション -->
        <div class="grid grid-cols-1 gap-5 mb-6 lg:grid-cols-2">
          <ReportRateTrendChart :data="reportRateTrend" />
          <UnderstandingDistributionChart :data="understandingDistribution" />
        </div>
        <div class="mb-6">
          <CurriculumScoreChart :data="curriculumScoreComparison" />
        </div>
      </template>

      <!-- instructor向け表示 -->
      <template v-else-if="user.role === 'instructor'">
        <div class="grid grid-cols-3 gap-5 mb-6">
          <StatCard
            label="担当カリキュラム要注意者"
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

        <div class="grid grid-cols-3 gap-5 mb-6">
          <div class="col-span-2">
            <CurriculumSummaryTable :summaries="curriculumSummaries" />
          </div>
          <RecentRiskAlertsCard :alerts="recentRiskAlerts" />
        </div>

        <!-- グラフセクション -->
        <div class="grid grid-cols-1 gap-5 mb-6 lg:grid-cols-2">
          <ReportRateTrendChart :data="reportRateTrend" />
          <UnderstandingDistributionChart :data="understandingDistribution" />
        </div>
        <div class="mb-6">
          <CurriculumScoreChart :data="curriculumScoreComparison" />
        </div>
      </template>

      <!-- student向け表示 -->
      <template v-else-if="user.role === 'student'">
        <!-- 未提出警告 -->
        <div v-if="studentStats.has_missing_report" class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-xl">
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

        <div class="grid grid-cols-2 gap-5 mb-6">
          <!-- 直近日報 -->
          <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
            <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-4">直近の日報</h2>
            <template v-if="studentStats.latest_report">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">日付</span>
                  <span class="text-sm font-medium">{{ formatDate(studentStats.latest_report.reported_on) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">理解度</span>
                  <UnderstandingBadge :level="studentStats.latest_report.understanding_level" />
                </div>
              </div>
              <Link
                :href="`/daily-reports/${studentStats.latest_report.id}`"
                class="mt-4 block text-sm text-indigo-600 hover:text-indigo-800"
              >
                詳細を見る
              </Link>
            </template>
            <p v-else class="text-sm text-slate-400">まだ日報がありません</p>
          </div>

          <!-- 直近テスト結果 -->
          <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
            <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-4">直近のテスト結果</h2>
            <template v-if="studentStats.latest_submission">
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">テスト</span>
                  <span class="text-sm font-medium truncate max-w-[10rem]">
                    {{ studentStats.latest_submission.test?.title }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">得点</span>
                  <span class="text-sm font-bold text-indigo-700">
                    {{ studentStats.latest_submission.score ?? '採点中' }}
                  </span>
                </div>
              </div>
              <Link
                :href="`/submissions/${studentStats.latest_submission.id}`"
                class="mt-4 block text-sm text-indigo-600 hover:text-indigo-800"
              >
                詳細を見る
              </Link>
            </template>
            <p v-else class="text-sm text-slate-400">まだ受験記録がありません</p>
          </div>
        </div>

        <UnderstandingTrendChart :trend="understandingTrend" />
      </template>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import type {
  PageProps,
  DailyReport,
  Submission,
  DashboardRiskAlert,
  CurriculumSummary,
  UnderstandingTrendItem,
  UnderstandingDistribution,
  ReportRateItem,
  CurriculumScoreItem,
} from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import UnderstandingBadge from '@/Components/UnderstandingBadge.vue';
import StatCard from '@/Components/StatCard.vue';
import RecentRiskAlertsCard from '@/Components/RecentRiskAlertsCard.vue';
import CurriculumSummaryTable from '@/Components/CurriculumSummaryTable.vue';
import UnderstandingTrendChart from '@/Components/UnderstandingTrendChart.vue';
import UnderstandingDistributionChart from '@/Components/UnderstandingDistributionChart.vue';
import ReportRateTrendChart from '@/Components/ReportRateTrendChart.vue';
import CurriculumScoreChart from '@/Components/CurriculumScoreChart.vue';
import { formatDate } from '@/utils/formatDate';

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
  recentRiskAlerts?: DashboardRiskAlert[];
  curriculumSummaries?: CurriculumSummary[];
  understandingTrend?: UnderstandingTrendItem[];
  understandingDistribution?: UnderstandingDistribution[];
  reportRateTrend?: ReportRateItem[];
  curriculumScoreComparison?: CurriculumScoreItem[];
}>();

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

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

const recentRiskAlerts = computed<DashboardRiskAlert[]>(() => props.recentRiskAlerts ?? []);
const curriculumSummaries = computed<CurriculumSummary[]>(() => props.curriculumSummaries ?? []);
const understandingTrend = computed<UnderstandingTrendItem[]>(() => props.understandingTrend ?? []);
const understandingDistribution = computed<UnderstandingDistribution[]>(() => props.understandingDistribution ?? []);
const reportRateTrend = computed<ReportRateItem[]>(() => props.reportRateTrend ?? []);
const curriculumScoreComparison = computed<CurriculumScoreItem[]>(() => props.curriculumScoreComparison ?? []);
</script>
