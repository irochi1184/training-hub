import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('日報コメントフロー', () => {
    test('instructor が日報にコメントを追加できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // 日報一覧に遷移
        await page.goto('/daily-reports');
        await page.waitForLoadState('networkidle');

        // 最初の日報行をクリック（行クリックで遷移する仕様）
        const firstRow = page.locator('tbody tr').first();
        await expect(firstRow).toBeVisible({ timeout: 5000 });
        await firstRow.click();
        await page.waitForLoadState('networkidle');

        // コメントを入力して送信
        await page.locator('textarea').fill('E2Eテストからのコメントです。');
        await page.getByRole('button', { name: '送信' }).click();

        // コメントが表示される
        await expect(page.getByText('E2Eテストからのコメントです。')).toBeVisible({ timeout: 10000 });
    });
});
