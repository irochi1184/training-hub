<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-slate-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
      <span v-if="optional" class="text-xs text-slate-400 font-normal">（任意）</span>
    </label>
    <p v-if="hint" class="text-xs text-slate-500 mb-2">{{ hint }}</p>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      class="block w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:ring-1 focus:outline-none transition-colors disabled:bg-slate-50 disabled:text-slate-500"
      :class="error
        ? 'border-red-400 focus:border-red-400 focus:ring-red-400'
        : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-500'"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  id?: string;
  label?: string;
  type?: string;
  modelValue?: string | number;
  placeholder?: string;
  required?: boolean;
  optional?: boolean;
  disabled?: boolean;
  hint?: string;
  error?: string;
}>();

defineEmits<{
  'update:modelValue': [value: string];
}>();
</script>
