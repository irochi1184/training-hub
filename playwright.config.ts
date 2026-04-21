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

    // ベースURL（ローカル開発サーバー）
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000',
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

    // 専用の開発サーバーを自動で立ち上げてテスト間の干渉を避ける
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/up',
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },

    // テスト結果の出力先
    outputDir: './playwright/test-results',
});
