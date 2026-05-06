<template>
  <AppLayout>
    <div class="max-w-2xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">マイプロフィール</h1>
        <Link
          href="/profile/edit"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
        >
          編集する
        </Link>
      </div>

      <!-- 未設定時 -->
      <div v-if="!profile" class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-8 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
        </svg>
        <p class="text-sm text-slate-500 mb-4">プロフィールがまだ設定されていません</p>
        <Link
          href="/profile/edit"
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors"
        >
          プロフィールを設定する
        </Link>
      </div>

      <!-- プロフィール表示 -->
      <div v-else class="space-y-5">
        <!-- 自己紹介 -->
        <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-3">自己紹介</h2>
          <p v-if="profile.bio" class="text-sm text-slate-600 whitespace-pre-wrap leading-relaxed">{{ profile.bio }}</p>
          <p v-else class="text-sm text-slate-400">未設定</p>
        </section>

        <!-- 学習目標 -->
        <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-3">学習目標</h2>
          <p v-if="profile.learning_goal" class="text-sm text-slate-600 whitespace-pre-wrap leading-relaxed">{{ profile.learning_goal }}</p>
          <p v-else class="text-sm text-slate-400">未設定</p>
        </section>

        <!-- スキル -->
        <section class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-3">スキル</h2>
          <div v-if="profile.skills && profile.skills.length > 0" class="flex flex-wrap gap-2">
            <span
              v-for="skill in profile.skills"
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
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { StudentProfile } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps<{
  profile: StudentProfile | null;
}>();

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
</script>
