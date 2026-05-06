<template>
  <AppLayout>
    <div class="max-w-5xl">
      <!-- ページヘッダー -->
      <div class="mb-6">
        <Link href="/students" class="text-sm text-slate-500 hover:text-slate-800 flex items-center gap-1 mb-3">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          受講生一覧に戻る
        </Link>
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ student.name }}</h1>
          <span
            v-if="hasUnresolvedAlert"
            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
          >
            要注意
          </span>
        </div>
      </div>

      <!-- 基本情報カード -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
        <h2 class="text-[11px] font-semibold text-slate-500 uppercase tracking-widest mb-4">基本情報</h2>
        <dl class="grid grid-cols-2 gap-4">
          <div>
            <dt class="text-xs text-slate-500">メールアドレス</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ student.email }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">カリキュラム</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ latestEnrollment?.curriculum?.name ?? '未登録' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">受講登録日</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ formatDate(latestEnrollment?.enrolled_at) }}
            </dd>
          </div>
        </dl>
      </div>

      <!-- サマリーセクション: 理解度推移 + テスト結果サマリー -->
      <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
        <!-- 理解度推移グラフ -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-4">理解度推移（直近{{ understandingTrend.length }}件）</h2>

          <div v-if="understandingTrend.length === 0" class="flex items-center justify-center h-32 text-sm text-slate-400">
            日報データがありません
          </div>

          <div v-else>
            <!-- 棒グラフ本体 -->
            <div class="flex items-end gap-1 h-32 overflow-x-auto pb-1" style="min-height: 8rem;">
              <div
                v-for="item in understandingTrend"
                :key="item.date"
                class="flex flex-col items-center flex-shrink-0"
                style="min-width: 1.5rem;"
                :title="`${item.date}：理解度 ${item.level ?? '未提出'}`"
              >
                <div
                  v-if="item.level !== null"
                  class="w-full rounded-t transition-all"
                  :class="understandingBarClass(item.level)"
                  :style="{ height: barHeightStyle(item.level) }"
                ></div>
                <div
                  v-else
                  class="w-full rounded-t bg-slate-100 border border-dashed border-slate-300"
                  style="height: 4px"
                ></div>
              </div>
            </div>

            <!-- 凡例 -->
            <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
              <span class="flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded-sm bg-red-400"></span>低い（1〜2）
              </span>
              <span class="flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded-sm bg-yellow-400"></span>普通（3）
              </span>
              <span class="flex items-center gap-1">
                <span class="inline-block w-3 h-3 rounded-sm bg-green-400"></span>高い（4〜5）
              </span>
            </div>

            <!-- 直近値 -->
            <div class="mt-3 flex items-center gap-2">
              <span class="text-xs text-slate-500">直近の理解度:</span>
              <UnderstandingBadge :level="latestUnderstandingLevel" />
            </div>
          </div>
        </div>

        <!-- テスト結果サマリー -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-4">テスト結果サマリー</h2>

          <div v-if="testSummary.count === 0" class="flex items-center justify-center h-32 text-sm text-slate-400">
            受験記録がありません
          </div>

          <div v-else class="grid grid-cols-2 gap-3">
            <!-- 受験数 -->
            <div class="rounded-xl bg-slate-50 ring-1 ring-slate-900/5 p-4 text-center">
              <div class="text-2xl font-bold text-slate-900">{{ testSummary.count }}</div>
              <div class="text-xs text-slate-500 mt-1">受験数</div>
            </div>

            <!-- 平均点 -->
            <div class="rounded-xl bg-indigo-50 ring-1 ring-indigo-200 p-4 text-center">
              <div class="text-2xl font-bold text-indigo-700">
                {{ testSummary.average !== null ? testSummary.average : '—' }}
              </div>
              <div class="text-xs text-indigo-500 mt-1">平均点</div>
            </div>

            <!-- 最高点 -->
            <div class="rounded-xl bg-emerald-50 ring-1 ring-emerald-200 p-4 text-center">
              <div class="text-2xl font-bold text-emerald-700">
                {{ testSummary.max !== null ? testSummary.max : '—' }}
              </div>
              <div class="text-xs text-emerald-500 mt-1">最高点</div>
            </div>

            <!-- 最低点 -->
            <div
              class="rounded-xl p-4 text-center"
              :class="isLowScore ? 'bg-red-50 ring-1 ring-red-200' : 'bg-slate-50 ring-1 ring-slate-900/5'"
            >
              <div
                class="text-2xl font-bold"
                :class="isLowScore ? 'text-red-600' : 'text-slate-700'"
              >
                {{ testSummary.min !== null ? testSummary.min : '—' }}
              </div>
              <div
                class="text-xs mt-1"
                :class="isLowScore ? 'text-red-400' : 'text-slate-500'"
              >
                最低点
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- タブ -->
      <div class="border-b border-slate-200/60 mb-6">
        <nav class="flex gap-6">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="pb-3 text-sm font-medium border-b-2 transition-colors"
            :class="activeTab === tab.key
              ? 'border-indigo-600 text-indigo-700'
              : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
            <span
              v-if="tab.count !== undefined"
              class="ml-1.5 text-xs text-slate-400"
            >{{ tab.count }}</span>
          </button>
        </nav>
      </div>

      <!-- タブコンテンツ: 日報一覧 -->
      <div v-if="activeTab === 'reports'">
        <DataTable
          :empty="dailyReports.length === 0"
          empty-message="日報がありません"
          :col-span="4"
        >
          <template #head>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">日付</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">理解度</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">学習内容（要約）</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">コメント</th>
          </template>
          <template #body>
            <tr
              v-for="report in dailyReports"
              :key="report.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-slate-900">{{ formatDate(report.reported_on) }}</td>
              <td class="px-4 py-3">
                <UnderstandingBadge :level="report.understanding_level" />
              </td>
              <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate">{{ report.content }}</td>
              <td class="px-4 py-3 text-sm text-slate-500">
                {{ report.comments?.length ?? 0 }} 件
              </td>
            </tr>
          </template>
        </DataTable>
      </div>

      <!-- タブコンテンツ: テスト結果一覧 -->
      <div v-else-if="activeTab === 'submissions'">
        <DataTable
          :empty="submissions.length === 0"
          empty-message="受験記録がありません"
          :col-span="4"
        >
          <template #head>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">テスト名</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">提出日時</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">得点</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"></th>
          </template>
          <template #body>
            <tr
              v-for="submission in submissions"
              :key="submission.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-slate-900">{{ submission.test?.title ?? '—' }}</td>
              <td class="px-4 py-3 text-sm text-slate-600">
                {{ submission.submitted_at ? formatDateTime(submission.submitted_at) : '未提出' }}
              </td>
              <td class="px-4 py-3 text-sm font-medium">
                <span v-if="submission.score !== null" class="text-slate-900">{{ submission.score }} 点</span>
                <span v-else class="text-slate-400">採点中</span>
              </td>
              <td class="px-4 py-3">
                <Link
                  :href="`/submissions/${submission.id}`"
                  class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                  詳細
                </Link>
              </td>
            </tr>
          </template>
        </DataTable>
      </div>

      <!-- タブコンテンツ: 要注意アラート -->
      <div v-else-if="activeTab === 'alerts'">
        <DataTable
          :empty="riskAlerts.length === 0"
          empty-message="要注意アラートはありません"
          :col-span="4"
        >
          <template #head>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">理由</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">詳細</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">発生日</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">状態</th>
          </template>
          <template #body>
            <tr
              v-for="alert in riskAlerts"
              :key="alert.id"
              class="hover:bg-slate-50 transition-colors"
            >
              <td class="px-4 py-3">
                <ReasonBadge :reason="alert.reason" />
              </td>
              <td class="px-4 py-3 text-sm text-slate-600">{{ alert.detail ?? '—' }}</td>
              <td class="px-4 py-3 text-sm text-slate-600">{{ formatDateTime(alert.created_at) }}</td>
              <td class="px-4 py-3">
                <span
                  v-if="alert.resolved_at"
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700"
                >
                  解消済
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
                >
                  未解消
                </span>
              </td>
            </tr>
          </template>
        </DataTable>
      </div>

      <!-- タブコンテンツ: プロフィール -->
      <div v-else-if="activeTab === 'profile'">
        <div v-if="!student.student_profile" class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-8 text-center">
          <p class="text-sm text-slate-400">プロフィールが設定されていません</p>
        </div>

        <div v-else class="space-y-5">
          <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">自己紹介</h2>
            <p v-if="student.student_profile.bio" class="text-sm text-slate-600 whitespace-pre-wrap leading-relaxed">{{ student.student_profile.bio }}</p>
            <p v-else class="text-sm text-slate-400">未設定</p>
          </section>

          <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">学習目標</h2>
            <p v-if="student.student_profile.learning_goal" class="text-sm text-slate-600 whitespace-pre-wrap leading-relaxed">{{ student.student_profile.learning_goal }}</p>
            <p v-else class="text-sm text-slate-400">未設定</p>
          </section>

          <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">スキル</h2>
            <div v-if="student.student_profile.skills && student.student_profile.skills.length > 0" class="flex flex-wrap gap-2">
              <span
                v-for="skill in student.student_profile.skills"
                :key="skill.skill_name"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm"
                :class="skillBadgeClass(skill.level)"
              >
                {{ skill.skill_name }}
                <span class="text-xs opacity-75">{{ levelLabel(skill.level) }}</span>
              </span>
            </div>
            <p v-else class="text-sm text-slate-400">未設定</p>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import type {
  User,
  Enrollment,
  DailyReport,
  Submission,
  RiskAlert,
  UnderstandingTrendItem,
  TestSummary,
  StudentProfile,
} from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import UnderstandingBadge from '@/Components/UnderstandingBadge.vue';
import ReasonBadge from '@/Components/ReasonBadge.vue';
import { formatDate, formatDateTime } from '@/utils/formatDate';
import { understandingBarClass } from '@/utils/understandingLevel';

const props = defineProps<{
  student: User & { student_profile?: StudentProfile | null };
  enrollments: Enrollment[];
  dailyReports: DailyReport[];
  submissions: Submission[];
  riskAlerts: RiskAlert[];
  understandingTrend: UnderstandingTrendItem[];
  testSummary: TestSummary;
}>();

type TabKey = 'reports' | 'submissions' | 'alerts' | 'profile';
const activeTab = ref<TabKey>('reports');

const tabs: { key: TabKey; label: string; count?: number }[] = [
  { key: 'reports', label: '日報一覧', count: props.dailyReports.length },
  { key: 'submissions', label: 'テスト結果', count: props.submissions.length },
  { key: 'alerts', label: '要注意アラート', count: props.riskAlerts.length },
  { key: 'profile', label: 'プロフィール' },
];

function levelLabel(level: number): string {
  const labels: Record<number, string> = { 1: '初級', 2: '中級', 3: '上級' };
  return labels[level] ?? '';
}

function skillBadgeClass(level: number): string {
  switch (level) {
    case 3: return 'bg-indigo-100 text-indigo-800';
    case 2: return 'bg-emerald-100 text-emerald-800';
    default: return 'bg-slate-100 text-slate-700';
  }
}

const latestEnrollment = computed(() => props.enrollments[0]);

const hasUnresolvedAlert = computed(() =>
  props.riskAlerts.some((a) => a.resolved_at === null),
);

// 理解度推移グラフ
const CHART_MAX_HEIGHT_PX = 128; // h-32 = 8rem = 128px

function barHeightStyle(level: number): string {
  // レベル1でも最低10%の高さを確保して視認性を上げる
  const minRatio = 0.1;
  const ratio = minRatio + ((level - 1) / 4) * (1 - minRatio);
  return `${Math.round(ratio * CHART_MAX_HEIGHT_PX)}px`;
}

const latestUnderstandingLevel = computed(() => {
  if (props.understandingTrend.length === 0) return 0;
  return props.understandingTrend[props.understandingTrend.length - 1].level ?? 0;
});

// 最低点が60点未満なら警告表示
const LOW_SCORE_THRESHOLD = 60;
const isLowScore = computed(
  () => props.testSummary.min !== null && props.testSummary.min < LOW_SCORE_THRESHOLD,
);
</script>
