<template>
  <AppLayout>
    <div class="max-w-2xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">お知らせ作成</h1>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 space-y-5">
          <!-- タイトル -->
          <FormInput
            id="title"
            label="タイトル"
            :required="true"
            v-model="form.title"
            placeholder="お知らせのタイトル"
            :error="form.errors.title"
          />

          <!-- 本文 -->
          <FormTextarea
            id="body"
            label="本文"
            :required="true"
            v-model="form.body"
            :rows="8"
            placeholder="お知らせの内容を記入してください"
            :error="form.errors.body"
          />

          <!-- 重要度 -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">重要度</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.priority" value="normal" class="text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-slate-700">通常</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.priority" value="important" class="text-red-600 focus:ring-red-500" />
                <span class="text-sm text-red-700 font-medium">重要</span>
              </label>
            </div>
          </div>

          <!-- 対象 -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              対象 <span class="text-red-500">*</span>
            </label>
            <div class="space-y-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.target_type" value="all" class="text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-slate-700">全員</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.target_type" value="curriculum" class="text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-slate-700">カリキュラム指定</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" v-model="form.target_type" value="user" class="text-indigo-600 focus:ring-indigo-500" />
                <span class="text-sm text-slate-700">個別ユーザー</span>
              </label>
            </div>
          </div>

          <!-- カリキュラム選択 -->
          <div v-if="form.target_type === 'curriculum'">
            <FormSelect
              id="target_id"
              label="対象カリキュラム"
              :required="true"
              v-model="form.target_id"
              :options="curriculumOptions"
              placeholder="選択してください"
              :error="form.errors.target_id"
            />
          </div>

          <!-- ユーザー選択 -->
          <div v-if="form.target_type === 'user'">
            <FormSelect
              id="target_id"
              label="対象ユーザー"
              :required="true"
              v-model="form.target_id"
              :options="studentOptions"
              placeholder="選択してください"
              :error="form.errors.target_id"
            />
          </div>
        </div>

        <!-- 送信ボタン -->
        <div class="flex justify-end gap-3">
          <Link
            href="/announcements"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            キャンセル
          </Link>
          <button
            type="submit"
            class="px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            :disabled="form.processing"
          >
            {{ form.processing ? '送信中...' : '公開する' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormInput from '@/Components/FormInput.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import FormSelect from '@/Components/FormSelect.vue';

const props = defineProps<{
  curricula: { id: number; name: string }[];
  students: { id: number; name: string }[];
}>();

const curriculumOptions = computed(() =>
  props.curricula.map((c) => ({ value: c.id, label: c.name })),
);

const studentOptions = computed(() =>
  props.students.map((s) => ({ value: s.id, label: s.name })),
);

const form = useForm({
  title: '',
  body: '',
  priority: 'normal',
  target_type: 'all',
  target_id: '',
  publish_now: true,
});

function submit(): void {
  form.post('/announcements');
}
</script>
