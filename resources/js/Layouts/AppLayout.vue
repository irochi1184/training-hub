<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- モバイルオーバーレイ -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        @click="sidebarOpen = false"
      />
    </Transition>

    <!-- サイドバー -->
    <aside
      class="fixed inset-y-0 left-0 z-50 w-60 bg-slate-900 flex flex-col transform transition-transform duration-200 lg:relative lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- ロゴ -->
      <div class="h-16 flex items-center justify-between px-6 border-b border-white/10">
        <Link href="/dashboard" class="text-lg font-bold text-white tracking-tight">
          Training Hub
        </Link>
        <button
          type="button"
          class="lg:hidden text-slate-400 hover:text-white transition-colors"
          @click="sidebarOpen = false"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- ナビゲーション -->
      <nav class="flex-1 py-5 overflow-y-auto">
        <ul class="space-y-1 px-4">
          <!-- 共通 -->
          <li>
            <NavLink href="/dashboard" :current="isCurrentPath('/dashboard')">
              ダッシュボード
            </NavLink>
          </li>

          <!-- admin / instructor 向け -->
          <template v-if="user.role === 'admin' || user.role === 'instructor'">
            <li class="pt-3 pb-1">
              <span class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">管理</span>
            </li>
            <li>
              <NavLink href="/students" :current="isCurrentPath('/students')">
                受講生一覧
              </NavLink>
            </li>
            <li>
              <NavLink href="/daily-reports" :current="isCurrentPath('/daily-reports')">
                日報一覧
              </NavLink>
            </li>
            <li>
              <NavLink href="/tests" :current="isCurrentPath('/tests')">
                テスト一覧
              </NavLink>
            </li>
            <li>
              <NavLink href="/risk-alerts" :current="isCurrentPath('/risk-alerts')">
                <span class="flex items-center gap-2">
                  要注意者一覧
                  <span
                    v-if="riskAlertCount > 0"
                    class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full"
                  >
                    {{ riskAlertCount > 99 ? '99+' : riskAlertCount }}
                  </span>
                </span>
              </NavLink>
            </li>
          </template>

          <!-- admin 向け -->
          <template v-if="user.role === 'admin'">
            <li class="pt-3 pb-1">
              <span class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">組織管理</span>
            </li>
            <li>
              <NavLink href="/curricula" :current="isCurrentPath('/curricula')">
                カリキュラム管理
              </NavLink>
            </li>
            <li>
              <NavLink href="/exports" :current="isCurrentPath('/exports')">
                CSV出力
              </NavLink>
            </li>
          </template>

          <!-- student 向け -->
          <template v-if="user.role === 'student'">
            <li class="pt-3 pb-1">
              <span class="px-3 text-[11px] font-semibold text-slate-500 uppercase tracking-widest">受講</span>
            </li>
            <li>
              <NavLink href="/daily-reports/create" :current="isCurrentPath('/daily-reports/create')">
                日報入力
              </NavLink>
            </li>
            <li>
              <NavLink href="/tests" :current="isCurrentPath('/tests')">
                テスト一覧
              </NavLink>
            </li>
          </template>
        </ul>
      </nav>
    </aside>

    <!-- メインエリア -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- ヘッダー -->
      <header class="h-16 bg-white border-b border-slate-200/60 flex items-center justify-between px-4 sm:px-6 shrink-0">
        <!-- モバイルメニューボタン -->
        <button
          type="button"
          class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-slate-700 transition-colors"
          @click="sidebarOpen = true"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <div class="flex items-center gap-3 sm:gap-4 ml-auto">
          <span class="text-sm text-slate-700 font-medium hidden sm:inline">{{ user.name }}</span>
          <span class="text-xs text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-full font-medium">{{ roleLabel }}</span>
          <button
            type="button"
            class="text-sm text-slate-400 hover:text-slate-700 transition-colors"
            @click="logout"
          >
            ログアウト
          </button>
        </div>
      </header>

      <!-- フラッシュメッセージ -->
      <FlashMessage />

      <!-- ページコンテンツ -->
      <main class="flex-1 p-4 sm:p-6 overflow-auto">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import FlashMessage from '@/Components/FlashMessage.vue';

// 子コンポーネントとして使うナビリンク（インライン定義）
import { defineComponent, h } from 'vue';

const NavLink = defineComponent({
  props: {
    href: { type: String, required: true },
    current: { type: Boolean, default: false },
  },
  setup(props, { slots }) {
    return () =>
      h(
        Link,
        {
          href: props.href,
          class: [
            'block px-3 py-2 rounded-lg text-sm transition-colors',
            props.current
              ? 'bg-white/10 text-white font-medium'
              : 'text-slate-400 hover:bg-white/5 hover:text-white',
          ],
        },
        slots.default,
      );
  },
});

const page = usePage<PageProps>();
const user = computed(() => page.props.auth.user);

const sidebarOpen = ref(false);

const riskAlertCount = computed(() => {
  const props = page.props as PageProps & { risk_alert_count?: number };
  return props.risk_alert_count ?? 0;
});

const roleLabel = computed(() => {
  const labels: Record<string, string> = {
    admin: '管理者',
    instructor: '講師',
    student: '受講生',
  };
  return labels[user.value.role] ?? user.value.role;
});

function isCurrentPath(path: string): boolean {
  return window.location.pathname === path;
}

function logout(): void {
  router.post('/logout');
}

// ページ遷移時にモバイルメニューを閉じる
router.on('navigate', () => {
  sidebarOpen.value = false;
});
</script>
