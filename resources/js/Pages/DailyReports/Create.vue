<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">日報入力</h1>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- コホート選択 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 space-y-5">
          <h2 class="text-sm font-semibold text-slate-700">基本情報</h2>

          <div>
            <label for="cohort_id" class="block text-sm font-medium text-slate-700 mb-1">
              コホート <span class="text-red-500">*</span>
            </label>
            <select
              id="cohort_id"
              v-model="form.cohort_id"
              required
              class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
              :class="form.errors.cohort_id
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
            >
              <option value="" disabled>選択してください</option>
              <option v-for="cohort in cohorts" :key="cohort.id" :value="cohort.id">
                {{ cohort.name }}
              </option>
            </select>
            <p v-if="form.errors.cohort_id" class="mt-1 text-xs text-red-600">{{ form.errors.cohort_id }}</p>
          </div>

          <div>
            <label for="reported_on" class="block text-sm font-medium text-slate-700 mb-1">
              日付 <span class="text-red-500">*</span>
            </label>
            <input
              id="reported_on"
              v-model="form.reported_on"
              type="date"
              required
              class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
              :class="form.errors.reported_on
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
            />
            <p v-if="form.errors.reported_on" class="mt-1 text-xs text-red-600">{{ form.errors.reported_on }}</p>
          </div>
        </div>

        <!-- 理解度 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <h2 class="text-sm font-semibold text-slate-700 mb-4">
            理解度 <span class="text-red-500">*</span>
          </h2>

          <div class="space-y-2">
            <label
              v-for="option in understandingOptions"
              :key="option.value"
              class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
              :class="form.understanding_level === option.value
                ? `${option.selectedClass} border-current`
                : 'border-slate-200 hover:border-slate-300'"
            >
              <input
                type="radio"
                :value="option.value"
                v-model="form.understanding_level"
                class="mt-0.5 shrink-0"
              />
              <div>
                <span class="text-sm font-medium" :class="form.understanding_level === option.value ? '' : 'text-slate-800'">
                  {{ option.value }}. {{ option.label }}
                </span>
                <p class="text-xs text-slate-500 mt-0.5">{{ option.description }}</p>
              </div>
            </label>
          </div>
          <p v-if="form.errors.understanding_level" class="mt-2 text-xs text-red-600">
            {{ form.errors.understanding_level }}
          </p>
        </div>

        <!-- 学習内容 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <div>
            <label for="content" class="block text-sm font-medium text-slate-700 mb-1">
              学習内容 <span class="text-red-500">*</span>
            </label>
            <p class="text-xs text-slate-500 mb-2">本日学習した内容を具体的に記述してください</p>
            <textarea
              id="content"
              v-model="form.content"
              rows="6"
              required
              placeholder="例: HTTPメソッド（GET / POST / PUT / DELETE）の違いを学んだ。RESTful APIの設計原則について..."
              class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors resize-y"
              :class="form.errors.content
                ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
                : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
            />
            <p v-if="form.errors.content" class="mt-1 text-xs text-red-600">{{ form.errors.content }}</p>
          </div>
        </div>

        <!-- 感想・気づき（任意） -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <div>
            <label for="impression" class="block text-sm font-medium text-slate-700 mb-1">
              感想・気づき <span class="text-xs text-slate-400 font-normal">（任意）</span>
            </label>
            <p class="text-xs text-slate-500 mb-2">疑問点や今後取り組みたいことなどを自由に記述してください</p>
            <textarea
              id="impression"
              v-model="form.impression"
              rows="4"
              placeholder="例: REST APIの設計でURLの命名が難しいと感じた。次回は実際にAPIを叩いて..."
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition-colors resize-y"
            />
            <p v-if="form.errors.impression" class="mt-1 text-xs text-red-600">{{ form.errors.impression }}</p>
          </div>
        </div>

        <!-- 提出ボタン -->
        <div class="flex justify-end gap-3">
          <Link
            href="/dashboard"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            キャンセル
          </Link>
          <button
            type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            :disabled="form.processing"
          >
            {{ form.processing ? '提出中...' : '日報を提出する' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { Cohort } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  cohorts: Cohort[];
  today: string; // 'YYYY-MM-DD'
}>();

const understandingOptions = [
  {
    value: 5,
    label: '十分に理解できた',
    description: '説明できる。応用できる。',
    selectedClass: 'bg-green-50 text-green-800 border-green-400',
  },
  {
    value: 4,
    label: 'だいたい理解できた',
    description: '大枠は分かった。細部に不安がある。',
    selectedClass: 'bg-lime-50 text-lime-800 border-lime-400',
  },
  {
    value: 3,
    label: 'なんとなく理解できた',
    description: '何となく分かったが自信がない。',
    selectedClass: 'bg-yellow-50 text-yellow-800 border-yellow-400',
  },
  {
    value: 2,
    label: 'あまり理解できなかった',
    description: '難しくて分からない部分が多かった。',
    selectedClass: 'bg-orange-50 text-orange-800 border-orange-400',
  },
  {
    value: 1,
    label: 'まったく理解できなかった',
    description: '内容がほとんど理解できなかった。',
    selectedClass: 'bg-red-50 text-red-800 border-red-400',
  },
];

const form = useForm({
  cohort_id: '',
  reported_on: props.today,
  understanding_level: 0 as number,
  content: '',
  impression: '',
});

function submit(): void {
  form.post('/daily-reports');
}
</script>
