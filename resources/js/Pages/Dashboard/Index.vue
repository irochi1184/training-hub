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

        <!-- 学習サマリーカード -->
        <div class="grid grid-cols-3 gap-5 mb-6">
          <StatCard
            label="日報提出率（30日）"
            :value="studentStats.report_rate ?? 0"
            unit="%"
          />
          <StatCard
            label="テスト平均点"
            :value="studentStats.test_avg_score ?? '—'"
            unit="点"
          />
          <StatCard
            label="受験済みテスト"
            :value="studentStats.test_count ?? 0"
            unit="回"
          />
        </div>

        <!-- カリキュラム別進捗 -->
        <div v-if="curriculumProgress.length > 0" class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-4">カリキュラム別進捗</h2>
          <div class="space-y-4">
            <div v-for="(cp, i) in curriculumProgress" :key="i">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm text-slate-700 font-medium">{{ cp.curriculum_name }}</span>
                <span class="text-xs text-slate-500">
                  {{ cp.taken_tests }}/{{ cp.total_tests }} テスト受験済み
                  <span v-if="cp.avg_score !== null" class="ml-2 font-medium text-indigo-600">
                    平均 {{ cp.avg_score }}点
                  </span>
                </span>
              </div>
              <div class="w-full bg-slate-100 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all"
                  :class="progressBarClass(cp)"
                  :style="{ width: progressPercent(cp) + '%' }"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- グラフ行 -->
        <div class="grid grid-cols-1 gap-5 mb-6 lg:grid-cols-2">
          <UnderstandingTrendChart :trend="understandingTrend" />
          <ScoreTrendChart :data="scoreTrend" />
        </div>

        <!-- 直近の活動 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-4">直近の活動</h2>
          <div v-if="recentActivities.length === 0" class="text-sm text-slate-400">
            まだ活動記録がありません
          </div>
          <div v-else class="space-y-2.5">
            <div
              v-for="(activity, i) in recentActivities"
              :key="i"
              class="flex items-center gap-3 text-sm"
            >
              <span
                class="w-2 h-2 rounded-full shrink-0"
                :class="activity.type === 'report' ? 'bg-blue-400' : 'bg-emerald-400'"
              />
              <span class="text-slate-400 w-24 shrink-0">{{ activity.date }}</span>
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium shrink-0"
                :class="activity.type === 'report' ? 'bg-blue-50 text-blue-600' : 'bg-emerald-50 text-emerald-600'"
              >
                {{ activity.type === 'report' ? '日報' : 'テスト' }}
              </span>
              <span class="text-slate-700 truncate flex-1">{{ activity.title }}</span>
              <span class="text-slate-500 shrink-0">{{ activity.detail }}</span>
            </div>
          </div>
        </div>
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
  ScoreTrendItem,
} from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import RecentRiskAlertsCard from '@/Components/RecentRiskAlertsCard.vue';
import CurriculumSummaryTable from '@/Components/CurriculumSummaryTable.vue';
import UnderstandingTrendChart from '@/Components/UnderstandingTrendChart.vue';
import UnderstandingDistributionChart from '@/Components/UnderstandingDistributionChart.vue';
import ReportRateTrendChart from '@/Components/ReportRateTrendChart.vue';
import CurriculumScoreChart from '@/Components/CurriculumScoreChart.vue';
import ScoreTrendChart from '@/Components/ScoreTrendChart.vue';

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
  report_rate?: number;
  test_avg_score?: number | null;
  test_count?: number;
}

interface CurriculumProgressItem {
  curriculum_name: string;
  total_tests: number;
  taken_tests: number;
  avg_score: number | null;
}

interface ActivityItem {
  type: 'report' | 'submission';
  date: string;
  title: string;
  detail: string;
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
  scoreTrend?: ScoreTrendItem[];
  curriculumProgress?: CurriculumProgressItem[];
  recentActivities?: ActivityItem[];
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
const scoreTrend = computed<ScoreTrendItem[]>(() => props.scoreTrend ?? []);
const curriculumProgress = computed<CurriculumProgressItem[]>(() => props.curriculumProgress ?? []);
const recentActivities = computed<ActivityItem[]>(() => props.recentActivities ?? []);

function progressPercent(cp: CurriculumProgressItem): number {
  return cp.total_tests > 0 ? Math.round((cp.taken_tests / cp.total_tests) * 100) : 0;
}

function progressBarClass(cp: CurriculumProgressItem): string {
  const pct = progressPercent(cp);
  if (pct >= 80) return 'bg-emerald-500';
  if (pct >= 50) return 'bg-indigo-500';
  return 'bg-amber-500';
}
</script>
