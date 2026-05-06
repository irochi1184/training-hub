<template>
  <div class="space-y-5">
    <!-- 名称 -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">
        名称 <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.name"
        type="text"
        required
        placeholder="例: IT研修、ロジック研修【Java】"
        class="block w-full rounded border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors"
        :class="form.errors.name
          ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
          : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
      />
      <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
    </div>

    <!-- メイン講師 -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">
        メイン講師 <span class="text-red-500">*</span>
      </label>
      <select
        multiple
        :value="form.main_instructor_ids"
        @change="onMainChange"
        class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors min-h-[5rem]"
        :class="form.errors.main_instructor_ids
          ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
          : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
      >
        <option v-for="instructor in instructors" :key="instructor.id" :value="instructor.id">
          {{ instructor.name }}
        </option>
      </select>
      <p class="mt-1 text-xs text-slate-500">Ctrl/Cmd を押しながらクリックで複数選択できます</p>
      <p v-if="form.errors.main_instructor_ids" class="mt-1 text-xs text-red-600">{{ form.errors.main_instructor_ids }}</p>
    </div>

    <!-- サブ講師 -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">
        サブ講師
      </label>
      <select
        multiple
        :value="form.sub_instructor_ids"
        @change="onSubChange"
        class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors min-h-[5rem]"
        :class="form.errors.sub_instructor_ids
          ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
          : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
      >
        <option v-for="instructor in availableSubInstructors" :key="instructor.id" :value="instructor.id">
          {{ instructor.name }}
        </option>
      </select>
      <p class="mt-1 text-xs text-slate-500">メイン講師に選択した講師は表示されません</p>
      <p v-if="form.errors.sub_instructor_ids" class="mt-1 text-xs text-red-600">{{ form.errors.sub_instructor_ids }}</p>
    </div>

    <!-- 期間 -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          開始日 <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.starts_on"
          type="date"
          required
          class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
          :class="form.errors.starts_on
            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
        />
        <p v-if="form.errors.starts_on" class="mt-1 text-xs text-red-600">{{ form.errors.starts_on }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
          終了日 <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.ends_on"
          type="date"
          required
          class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
          :class="form.errors.ends_on
            ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
            : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
        />
        <p v-if="form.errors.ends_on" class="mt-1 text-xs text-red-600">{{ form.errors.ends_on }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';

interface InstructorOption {
  id: number;
  name: string;
}

interface CurriculumFormShape {
  name: string;
  main_instructor_ids: number[];
  sub_instructor_ids: number[];
  starts_on: string;
  ends_on: string;
}

const props = defineProps<{
  form: InertiaForm<CurriculumFormShape>;
  instructors: InstructorOption[];
}>();

const availableSubInstructors = computed(() =>
  props.instructors.filter(i => !props.form.main_instructor_ids.includes(i.id))
);

function onMainChange(e: Event): void {
  const select = e.target as HTMLSelectElement;
  const ids = Array.from(select.selectedOptions, o => Number(o.value));
  props.form.main_instructor_ids = ids;
  // メインに選ばれた講師をサブから除外
  props.form.sub_instructor_ids = props.form.sub_instructor_ids.filter(id => !ids.includes(id));
}

function onSubChange(e: Event): void {
  const select = e.target as HTMLSelectElement;
  props.form.sub_instructor_ids = Array.from(select.selectedOptions, o => Number(o.value));
}
</script>
