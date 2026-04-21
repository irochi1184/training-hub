import { execSync } from 'node:child_process';

/**
 * Playwright 実行前に DB をリセットしてシードを流し直す。
 * 既存データの積み上がりで ID がずれて spec が失敗することを防ぐため。
 */
async function globalSetup(): Promise<void> {
    execSync('php artisan migrate:fresh --seed --force', { stdio: 'inherit' });
}

export default globalSetup;
