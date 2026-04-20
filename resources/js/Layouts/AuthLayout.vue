<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center">
    <!-- アプリ名 -->
    <div class="mb-8 text-center">
      <h1 class="text-3xl font-bold text-gray-800 tracking-tight">Training Hub</h1>
      <p class="mt-1 text-sm text-gray-500">企業研修受講管理システム</p>
    </div>

    <!-- カード -->
    <div class="w-full max-w-md bg-white rounded-lg shadow-md p-8">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
// ログイン画面専用のシンプルレイアウト
</script>
