<template>
  <AppLayout>
    <div class="max-w-5xl">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-6">受講生登録管理</h1>

      <!-- カリキュラム選択 -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
        <div class="flex items-end gap-4">
          <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">カリキュラム</label>
            <select
              v-model="selectedId"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
              @change="changeCurriculum"
            >
              <option v-for="c in curricula" :key="c.id" :value="c.id">
                {{ c.name }}（{{ c.enrollments_count }}名）
              </option>
            </select>
          </div>
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
            @click="showBulkModal = true"
          >
            一括登録
          </button>
        </div>
      </div>

      <!-- 受講生追加 -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6 mb-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">受講生を追加</h2>
        <div v-if="availableStudents.length === 0" class="text-sm text-slate-400">
          追加可能な受講生がいません
        </div>
        <form v-else @submit.prevent="addStudent" class="flex items-end gap-3">
          <div class="flex-1">
            <select
              v-model="addForm.user_id"
              class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
            >
              <option value="" disabled>受講生を選択</option>
              <option v-for="s in availableStudents" :key="s.id" :value="s.id">
                {{ s.name }}（{{ s.email }}）
              </option>
            </select>
          </div>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg shadow-sm hover:bg-emerald-700 disabled:opacity-50 transition-colors"
            :disabled="!addForm.user_id || addForm.processing"
          >
            登録
          </button>
        </form>
      </div>

      <!-- 登録済み一覧 -->
      <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-700">登録済み受講生（{{ enrollments.length }}名）</h2>
        </div>

        <div v-if="enrollments.length === 0" class="px-6 py-8 text-center text-sm text-slate-400">
          受講生が登録されていません
        </div>

        <table v-else class="w-full">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">名前</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">メール</th>
              <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">登録日</th>
              <th class="px-6 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="enrollment in enrollments" :key="enrollment.id" class="hover:bg-slate-50">
              <td class="px-6 py-3 text-sm font-medium text-slate-900">{{ enrollment.user?.name }}</td>
              <td class="px-6 py-3 text-sm text-slate-600">{{ enrollment.user?.email }}</td>
              <td class="px-6 py-3 text-sm text-slate-600">{{ enrollment.enrolled_at }}</td>
              <td class="px-6 py-3 text-right">
                <button
                  type="button"
                  class="text-sm text-red-600 hover:text-red-800 font-medium"
                  @click="removeEnrollment(enrollment)"
                >
                  解除
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 一括登録モーダル -->
    <div
      v-if="showBulkModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
      @click.self="showBulkModal = false"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-bold text-slate-900 mb-4">一括登録</h3>
        <p class="text-sm text-slate-500 mb-3">
          登録する受講生のメールアドレスを1行ずつ、またはカンマ区切りで入力してください。
        </p>
        <form @submit.prevent="bulkAdd">
          <textarea
            v-model="bulkForm.emails"
            rows="6"
            placeholder="student1@example.com&#10;student2@example.com"
            class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none resize-y mb-4"
          />
          <div class="flex justify-end gap-3">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              @click="showBulkModal = false"
            >
              キャンセル
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50 transition-colors"
              :disabled="!bulkForm.emails.trim() || bulkForm.processing"
            >
              {{ bulkForm.processing ? '登録中...' : '一括登録する' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import type { Curriculum, Enrollment, User } from '@/types';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps<{
  curricula: (Curriculum & { enrollments_count: number })[];
  selectedCurriculumId: number;
  enrollments: Enrollment[];
  availableStudents: User[];
}>();

const selectedId = ref(props.selectedCurriculumId);
const showBulkModal = ref(false);

function changeCurriculum(): void {
  router.get('/enrollments', { curriculum_id: selectedId.value }, { preserveState: true });
}

const addForm = useForm({
  curriculum_id: props.selectedCurriculumId,
  user_id: '' as string | number,
});

function addStudent(): void {
  addForm.curriculum_id = selectedId.value;
  addForm.post('/enrollments', { preserveScroll: true });
}

const bulkForm = useForm({
  curriculum_id: props.selectedCurriculumId,
  emails: '',
});

function bulkAdd(): void {
  bulkForm.curriculum_id = selectedId.value;
  bulkForm.post('/enrollments/bulk', {
    preserveScroll: true,
    onSuccess: () => {
      showBulkModal.value = false;
      bulkForm.emails = '';
    },
  });
}

function removeEnrollment(enrollment: Enrollment): void {
  if (!confirm(`${enrollment.user?.name} の登録を解除しますか？`)) return;
  router.delete(`/enrollments/${enrollment.id}`, { preserveScroll: true });
}
</script>
