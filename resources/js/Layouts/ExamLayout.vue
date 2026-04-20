<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- ヘッダー（最小化。テスト名と残り時間のみ） -->
    <header class="bg-white border-b border-gray-200 shrink-0">
      <div class="max-w-4xl mx-auto h-14 flex items-center justify-between px-6">
        <!-- テスト名 -->
        <div class="flex items-center gap-3">
          <span class="text-xs text-gray-400 font-semibold uppercase tracking-wider">受験中</span>
          <h1 class="text-base font-semibold text-gray-800">{{ testTitle }}</h1>
        </div>

        <!-- 残り時間 -->
        <div v-if="timeLimitMinutes" class="flex items-center gap-2">
          <span class="text-sm text-gray-500">残り時間</span>
          <span
            class="text-lg font-mono font-bold tabular-nums"
            :class="timeWarning ? 'text-red-600' : 'text-gray-800'"
          >
            {{ formattedTime }}
          </span>
        </div>
        <div v-else class="text-sm text-gray-400">
          時間制限なし
        </div>
      </div>
    </header>

    <!-- メインコンテンツ -->
    <main class="flex-1 overflow-auto">
      <div class="max-w-4xl mx-auto py-8 px-6">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  testTitle: string;
  timeLimitMinutes?: number | null;
  startedAt?: string; // ISO 8601形式
}>();

const emit = defineEmits<{
  timeUp: [];
}>();

// 残り秒数
const remainingSeconds = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  if (!props.timeLimitMinutes || !props.startedAt) return;

  const startMs = new Date(props.startedAt).getTime();
  const limitMs = props.timeLimitMinutes * 60 * 1000;
  const endMs = startMs + limitMs;

  function tick(): void {
    const now = Date.now();
    const diff = Math.max(0, Math.floor((endMs - now) / 1000));
    remainingSeconds.value = diff;
    if (diff <= 0) {
      if (timer) clearInterval(timer);
      emit('timeUp');
    }
  }

  tick();
  timer = setInterval(tick, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

const formattedTime = computed(() => {
  const total = remainingSeconds.value;
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  const mm = String(m).padStart(2, '0');
  const ss = String(s).padStart(2, '0');
  return h > 0 ? `${h}:${mm}:${ss}` : `${mm}:${ss}`;
});

// 残り5分を切ったら警告色
const timeWarning = computed(() => remainingSeconds.value <= 300 && remainingSeconds.value > 0);
</script>
