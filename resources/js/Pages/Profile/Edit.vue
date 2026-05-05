<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">プロフィール編集</h1>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- 自己紹介 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <div>
            <label for="bio" class="block text-sm font-medium text-slate-700 mb-1">
              自己紹介 <span class="text-xs text-slate-400 font-normal">（任意・1000文字以内）</span>
            </label>
            <textarea
              id="bio"
              v-model="form.bio"
              rows="4"
              placeholder="自己紹介を入力してください"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors resize-y"
            />
            <p v-if="form.errors.bio" class="mt-1 text-xs text-red-600">{{ form.errors.bio }}</p>
          </div>
        </div>

        <!-- 学習目標 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <div>
            <label for="learning_goal" class="block text-sm font-medium text-slate-700 mb-1">
              学習目標 <span class="text-xs text-slate-400 font-normal">（任意・500文字以内）</span>
            </label>
            <textarea
              id="learning_goal"
              v-model="form.learning_goal"
              rows="3"
              placeholder="例: 3ヶ月以内にWebアプリを一人で作れるようになる"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors resize-y"
            />
            <p v-if="form.errors.learning_goal" class="mt-1 text-xs text-red-600">{{ form.errors.learning_goal }}</p>
          </div>
        </div>

        <!-- スキル -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-700">スキル（最大10個）</h2>
            <button
              v-if="form.skills.length < 10"
              type="button"
              class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
              @click="addSkill"
            >
              + 追加
            </button>
          </div>

          <div v-if="form.skills.length === 0" class="text-sm text-slate-400 text-center py-4">
            スキルを追加してください
          </div>

          <div class="space-y-3">
            <div
              v-for="(skill, index) in form.skills"
              :key="index"
              class="flex items-center gap-3"
            >
              <input
                v-model="skill.skill_name"
                type="text"
                placeholder="スキル名（例: HTML）"
                class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              />
              <select
                v-model="skill.level"
                class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              >
                <option :value="1">初級</option>
                <option :value="2">中級</option>
                <option :value="3">上級</option>
              </select>
              <button
                type="button"
                class="text-slate-400 hover:text-red-500 transition-colors"
                @click="removeSkill(index)"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          <p v-if="form.errors.skills" class="mt-2 text-xs text-red-600">{{ form.errors.skills }}</p>
        </div>

        <!-- 送信ボタン -->
        <div class="flex justify-end gap-3">
          <Link
            href="/profile"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            キャンセル
          </Link>
          <button
            type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            :disabled="form.processing"
          >
            {{ form.processing ? '保存中...' : '保存する' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { StudentProfile } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  profile: StudentProfile | null;
}>();

const form = useForm({
  bio: props.profile?.bio ?? '',
  learning_goal: props.profile?.learning_goal ?? '',
  skills: (props.profile?.skills ?? []).map((s) => ({
    skill_name: s.skill_name,
    level: s.level as number,
  })),
});

function addSkill(): void {
  form.skills.push({ skill_name: '', level: 1 });
}

function removeSkill(index: number): void {
  form.skills.splice(index, 1);
}

function submit(): void {
  form.put('/profile');
}
</script>
