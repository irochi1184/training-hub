<template>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 p-6">
    <h2 class="text-sm font-semibold text-slate-700 border-b border-slate-100 pb-3 mb-4">
      CSVインポート
    </h2>

    <p class="text-xs text-slate-500 mb-3">
      CSVファイルで問題を一括登録できます。既存の問題に追加されます。
    </p>

    <!-- フォーマット説明 -->
    <details class="mb-4">
      <summary class="text-xs text-indigo-600 cursor-pointer hover:text-indigo-800">
        CSVフォーマットを確認
      </summary>
      <div class="mt-2 p-3 bg-slate-50 rounded-lg text-xs text-slate-600 space-y-1">
        <p class="font-medium">ヘッダー行（1行目）:</p>
        <code class="block bg-white px-2 py-1 rounded text-[11px]">
          問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2,選択肢3,正解3,選択肢4,正解4
        </code>
        <p class="mt-2 font-medium">データ行の例:</p>
        <code class="block bg-white px-2 py-1 rounded text-[11px]">
          PHPの変数は何で始まる？,single,10,$,1,#,0,@,0,&amp;,0
        </code>
        <ul class="mt-2 list-disc list-inside space-y-0.5">
          <li>問題タイプ: <code>single</code>（単一選択）/ <code>multiple</code>（複数選択）</li>
          <li>正解フラグ: <code>1</code>=正解, <code>0</code>=不正解</li>
          <li>選択肢は2〜8個まで（2列ずつ追加）</li>
        </ul>
      </div>
    </details>

    <!-- ファイル選択 -->
    <div class="flex items-center gap-3">
      <label
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg cursor-pointer hover:bg-indigo-100 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        CSVファイルを選択
        <input
          ref="fileInput"
          type="file"
          accept=".csv,.txt"
          class="sr-only"
          @change="onFileSelected"
        />
      </label>
      <span v-if="selectedFile" class="text-sm text-slate-600">{{ selectedFile.name }}</span>
      <button
        v-if="selectedFile"
        type="button"
        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
        :disabled="uploading"
        @click="upload"
      >
        {{ uploading ? 'インポート中...' : 'インポート実行' }}
      </button>
    </div>

    <!-- 成功メッセージ -->
    <div v-if="successMessage" class="mt-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
      {{ successMessage }}
    </div>

    <!-- エラー表示 -->
    <div v-if="errors.length > 0" class="mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
      <p class="text-sm font-medium text-red-700 mb-2">インポートエラー:</p>
      <ul class="list-disc list-inside space-y-0.5 text-xs text-red-600">
        <li v-for="(err, i) in errors" :key="i">{{ err }}</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps<{
  testId: number;
}>();

const emit = defineEmits<{
  imported: [];
}>();

const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File | null>(null);
const uploading = ref(false);
const successMessage = ref('');
const errors = ref<string[]>([]);

function onFileSelected(event: Event): void {
  const input = event.target as HTMLInputElement;
  selectedFile.value = input.files?.[0] ?? null;
  successMessage.value = '';
  errors.value = [];
}

async function upload(): Promise<void> {
  if (!selectedFile.value) return;

  uploading.value = true;
  successMessage.value = '';
  errors.value = [];

  const formData = new FormData();
  formData.append('csv_file', selectedFile.value);

  try {
    const response = await axios.post(`/tests/${props.testId}/import`, formData);
    successMessage.value = response.data.message;
    selectedFile.value = null;
    if (fileInput.value) fileInput.value.value = '';
    emit('imported');
  } catch (err: any) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      errors.value = err.response.data.errors;
    } else if (err.response?.data?.message) {
      errors.value = [err.response.data.message];
    } else {
      errors.value = ['アップロードに失敗しました。'];
    }
  } finally {
    uploading.value = false;
  }
}
</script>
