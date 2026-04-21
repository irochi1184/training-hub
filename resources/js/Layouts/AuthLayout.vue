<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex flex-col justify-center items-center">
    <!-- アプリ名 -->
    <div class="mb-8 text-center">
      <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Training Hub</h1>
      <p class="mt-2 text-sm text-slate-500">企業研修受講管理システム</p>
    </div>

    <!-- カード -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg ring-1 ring-slate-900/5 p-8">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
// ログイン画面専用のシンプルレイアウト
</script>
