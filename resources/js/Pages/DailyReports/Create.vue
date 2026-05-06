<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">日報入力</h1>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- カリキュラム選択 -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 space-y-5">
          <h2 class="text-sm font-semibold text-slate-700">基本情報</h2>

          <FormSelect
            id="curriculum_id"
            label="カリキュラム"
            :required="true"
            v-model="form.curriculum_id"
            :options="curriculumOptions"
            placeholder="選択してください"
            :error="form.errors.curriculum_id"
          />

          <FormInput
            id="reported_on"
            label="日付"
            type="date"
            :required="true"
            v-model="form.reported_on"
            :error="form.errors.reported_on"
          />
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
          <FormTextarea
            id="content"
            label="学習内容"
            :required="true"
            hint="本日学習した内容を具体的に記述してください"
            v-model="form.content"
            :rows="6"
            placeholder="例: HTTPメソッド（GET / POST / PUT / DELETE）の違いを学んだ。RESTful APIの設計原則について..."
            :error="form.errors.content"
          />
        </div>

        <!-- 感想・気づき（任意） -->
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
          <FormTextarea
            id="impression"
            label="感想・気づき"
            :optional="true"
            hint="疑問点や今後取り組みたいことなどを自由に記述してください"
            v-model="form.impression"
            :rows="4"
            placeholder="例: REST APIの設計でURLの命名が難しいと感じた。次回は実際にAPIを叩いて..."
            :error="form.errors.impression"
          />
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
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import type { Curriculum } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import FormSelect from '@/Components/FormSelect.vue';
import FormTextarea from '@/Components/FormTextarea.vue';

const props = defineProps<{
  curricula: Curriculum[];
  today: string; // 'YYYY-MM-DD'
}>();

const curriculumOptions = computed(() =>
  props.curricula.map((c) => ({ value: c.id, label: c.name })),
);

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
  curriculum_id: '',
  reported_on: props.today,
  understanding_level: 0 as number,
  content: '',
  impression: '',
});

function submit(): void {
  form.post('/daily-reports');
}
</script>
