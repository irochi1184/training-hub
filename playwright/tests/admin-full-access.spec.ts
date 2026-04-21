import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('admin 全権フロー', () => {
    test('admin がすべての主要画面に到達できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        // ダッシュボード
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(page.getByRole('heading', { name: 'ダッシュボード' })).toBeVisible();

        // 受講生一覧
        await page.getByRole('link', { name: /受講生/ }).first().click();
        await expect(page).toHaveURL(/\/students/);
        await expect(page.getByRole('heading', { name: '受講生一覧' })).toBeVisible();

        // 日報一覧
        await page.getByRole('link', { name: /日報/ }).first().click();
        await expect(page).toHaveURL(/\/daily-reports/);

        // テスト一覧
        await page.getByRole('link', { name: /テスト/ }).first().click();
        await expect(page).toHaveURL(/\/tests/);
        await expect(page.getByRole('heading', { name: 'テスト一覧' })).toBeVisible();

        // 要注意者一覧
        await page.goto('/risk-alerts');
        await expect(page.getByRole('heading', { name: '要注意者一覧' })).toBeVisible();

        // CSV出力
        await page.goto('/exports');
        await expect(page.getByRole('heading', { name: 'CSV出力' })).toBeVisible();
    });

    test('admin が受講生詳細を閲覧できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);
        await page.goto('/students');
        await page.waitForLoadState('networkidle');

        // 受講生行の名前リンク(/students/:id へ)をクリック
        const detailLink = page.locator('a[href^="/students/"]').first();
        await detailLink.waitFor({ timeout: 5000 });
        await detailLink.click();
        await expect(page).toHaveURL(/\/students\/\d+/);
    });
});
