import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('ダッシュボードグラフ表示', () => {
    test('admin ダッシュボードにカリキュラム別サマリが表示される', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');

        // StatCard が表示される
        await expect(page.getByText('要注意者（未解消）')).toBeVisible({ timeout: 5000 });
        await expect(page.getByText('本日の日報提出率')).toBeVisible();
        await expect(page.getByText('テスト受験完了率')).toBeVisible();

        // カリキュラム別サマリテーブルが表示される
        await expect(page.getByText('カリキュラム別サマリ')).toBeVisible();
        await expect(page.getByRole('cell', { name: 'IT研修' })).toBeVisible();
    });

    test('instructor ダッシュボードに担当カリキュラム情報が表示される', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');

        await expect(page.getByText('担当カリキュラム要注意者')).toBeVisible({ timeout: 5000 });
        await expect(page.getByText('カリキュラム別サマリ')).toBeVisible();
    });

    test('student ダッシュボードに直近の日報と理解度が表示される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');

        // student にはカリキュラム別サマリが表示されない
        await expect(page.getByText('カリキュラム別サマリ')).not.toBeVisible();

        // 直近の日報セクションが表示される
        await expect(page.getByText('直近の日報')).toBeVisible({ timeout: 5000 });
    });
});
