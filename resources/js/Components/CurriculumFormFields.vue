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

    <!-- 担当講師 -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">
        担当講師 <span class="text-red-500">*</span>
      </label>
      <select
        v-model="form.instructor_id"
        required
        class="block w-full rounded border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors"
        :class="form.errors.instructor_id
          ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
          : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
      >
        <option value="" disabled>選択してください</option>
        <option v-for="instructor in instructors" :key="instructor.id" :value="instructor.id">
          {{ instructor.name }}
        </option>
      </select>
      <p v-if="form.errors.instructor_id" class="mt-1 text-xs text-red-600">{{ form.errors.instructor_id }}</p>
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
import type { InertiaForm } from '@inertiajs/vue3';

interface InstructorOption {
  id: number;
  name: string;
}

interface CurriculumFormShape {
  name: string;
  instructor_id: number | '';
  starts_on: string;
  ends_on: string;
}

defineProps<{
  form: InertiaForm<CurriculumFormShape>;
  instructors: InstructorOption[];
}>();
</script>
