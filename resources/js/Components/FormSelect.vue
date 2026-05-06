<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-slate-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 focus:ring-1 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500"
      :class="error
        ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
        : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
export interface SelectOption {
  value: string | number;
  label: string;
}

defineProps<{
  id?: string;
  label?: string;
  modelValue?: string | number;
  options: SelectOption[];
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
}>();

defineEmits<{
  'update:modelValue': [value: string];
}>();
</script>
