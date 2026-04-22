<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-slate-900 mb-6">カリキュラム編集</h1>

      <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 space-y-5">
        <CurriculumFormFields :form="form" :instructors="instructors" />

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
          <Link
            href="/curricula"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded hover:bg-slate-50 transition-colors"
          >
            キャンセル
          </Link>
          <button
            type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            :disabled="form.processing"
          >
            {{ form.processing ? '保存中...' : '変更を保存する' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { Curriculum } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';
import CurriculumFormFields from '@/Components/CurriculumFormFields.vue';

interface InstructorOption {
  id: number;
  name: string;
}

const props = defineProps<{
  curriculum: Curriculum;
  instructors: InstructorOption[];
}>();

const form = useForm<{
  name: string;
  instructor_id: number | '';
  starts_on: string;
  ends_on: string;
}>({
  name: props.curriculum.name,
  instructor_id: props.curriculum.instructor_id,
  starts_on: props.curriculum.starts_on,
  ends_on: props.curriculum.ends_on,
});

function submit(): void {
  form.put(`/curricula/${props.curriculum.id}`);
}
</script>
