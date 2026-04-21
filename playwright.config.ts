import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    // テストファイルのディレクトリ
    testDir: './playwright/tests',

    // 実行前に DB をリセットしてシードを再投入する
    globalSetup: './playwright/global-setup.ts',

    // 並列実行数（CIでは1に落とすことを推奨）
    workers: 1,

    // テストがタイムアウトするまでの時間
    timeout: 30_000,

    // ベースURL（Sail コンテナをそのまま使う）
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://localhost',
        // スクリーンショットは失敗時のみ保存
        screenshot: 'only-on-failure',
        // 追跡は失敗時のみ保存
        trace: 'retain-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    // テスト結果の出力先
    outputDir: './playwright/test-results',
});
