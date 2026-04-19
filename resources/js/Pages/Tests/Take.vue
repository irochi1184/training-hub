<template>
  <ExamLayout
    :test-title="test.title"
    :time-limit-minutes="test.time_limit_minutes"
    :started-at="submission.started_at"
    @time-up="handleTimeUp"
  >
    <!-- テスト説明 -->
    <div v-if="test.description" class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-6 text-sm text-blue-800">
      {{ test.description }}
    </div>

    <!-- 未回答警告 -->
    <div
      v-if="showUnansweredWarning"
      class="bg-yellow-50 border border-yellow-300 rounded-lg px-4 py-3 mb-6 text-sm text-yellow-800"
    >
      まだ回答していない問題があります（{{ unansweredCount }} 問）。提出前に確認してください。
    </div>

    <!-- 問題リスト -->
    <div class="space-y-6">
      <div
        v-for="(question, index) in test.questions"
        :key="question.id"
        class="bg-white rounded-lg border transition-colors"
        :class="answers[question.id] ? 'border-gray-200' : 'border-orange-200'"
      >
        <!-- 問題ヘッダー -->
        <div class="flex items-start justify-between px-5 pt-5 pb-3">
          <div class="flex items-start gap-3">
            <span class="shrink-0 w-7 h-7 rounded-full bg-gray-100 text-gray-600 text-sm font-bold flex items-center justify-center">
              {{ index + 1 }}
            </span>
            <p class="text-sm font-medium text-gray-900 leading-relaxed">{{ question.body }}</p>
          </div>
          <span class="shrink-0 text-xs text-gray-400 ml-4">{{ question.score }}点</span>
        </div>

        <!-- 選択肢 -->
        <div class="px-5 pb-5 space-y-2">
          <label
            v-for="choice in question.choices"
            :key="choice.id"
            class="flex items-center gap-3 p-3 rounded border cursor-pointer transition-colors"
            :class="answers[question.id] === choice.id
              ? 'bg-blue-50 border-blue-400'
              : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
          >
            <input
              type="radio"
              :name="`question_${question.id}`"
              :value="choice.id"
              v-model="answers[question.id]"
              class="text-blue-600 focus:ring-blue-500"
            />
            <span class="text-sm text-gray-800">{{ choice.body }}</span>
          </label>
        </div>
      </div>
    </div>

    <!-- 提出ボタン -->
    <div class="mt-8 flex items-center justify-between bg-white rounded-lg border border-gray-200 px-6 py-4">
      <div class="text-sm text-gray-500">
        <span class="font-medium text-gray-800">{{ answeredCount }}</span> / {{ test.questions?.length ?? 0 }} 問 回答済み
      </div>
      <button
        type="button"
        class="px-6 py-2.5 text-sm font-medium text-white rounded transition-colors"
        :class="allAnswered ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 hover:bg-gray-500'"
        @click="requestSubmit"
      >
        提出する
      </button>
    </div>

    <!-- 提出確認ダイアログ -->
    <ConfirmDialog
      v-model="showSubmitDialog"
      :title="allAnswered ? 'テストを提出する' : '未回答の問題があります'"
      :message="allAnswered
        ? 'テストを提出します。提出後は回答を変更できません。'
        : `${unansweredCount} 問が未回答です。このまま提出しますか？`"
      confirm-label="提出する"
      :danger="!allAnswered"
      @confirm="doSubmit"
    />
  </ExamLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import type { Test, Submission } from '@/types';
import ExamLayout from '@/Layouts/ExamLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps<{
  test: Test;
  submission: Submission;
}>();

// 回答状態: { [question_id]: choice_id }
const answers = ref<Record<number, number | null>>({});

const answeredCount = computed(() =>
  Object.values(answers.value).filter((v) => v !== null && v !== undefined).length,
);

const unansweredCount = computed(() =>
  (props.test.questions?.length ?? 0) - answeredCount.value,
);

const allAnswered = computed(() =>
  answeredCount.value === (props.test.questions?.length ?? 0),
);

const showSubmitDialog = ref(false);
const showUnansweredWarning = ref(false);

function requestSubmit(): void {
  if (!allAnswered.value) {
    showUnansweredWarning.value = true;
  }
  showSubmitDialog.value = true;
}

const submitForm = useForm<{
  answers: { question_id: number; choice_id: number | null }[];
}>({
  answers: [],
});

function doSubmit(): void {
  submitForm.answers = (props.test.questions ?? []).map((q) => ({
    question_id: q.id,
    choice_id: answers.value[q.id] ?? null,
  }));
  submitForm.post(`/tests/${props.test.id}/submissions`);
}

// 時間切れ時は自動提出
function handleTimeUp(): void {
  doSubmit();
}
</script>
