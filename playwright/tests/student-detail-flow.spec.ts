import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('受講生詳細フロー', () => {
    test('admin が受講生詳細を閲覧でき、各セクションが表示される', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/students');
        await page.waitForLoadState('networkidle');

        // 最初の受講生のリンクをクリック
        await page.locator('tbody tr a').first().click();
        await page.waitForLoadState('networkidle');

        // 受講生名がページタイトルに表示される
        await expect(page.locator('h1')).toBeVisible({ timeout: 5000 });

        // 基本情報セクション
        await expect(page.getByText('基本情報')).toBeVisible();

        // 理解度推移チャートが表示される
        await expect(page.getByText('理解度推移')).toBeVisible();

        // テスト結果サマリーが表示される
        await expect(page.getByText('テスト結果サマリー')).toBeVisible();

        // タブが表示される（「日報一覧」「テスト結果」「要注意アラート」+ 件数）
        await expect(page.getByRole('button', { name: /日報一覧/ })).toBeVisible();
        await expect(page.getByRole('button', { name: /テスト結果/ })).toBeVisible();
    });

    test('instructor が担当受講生の詳細を閲覧できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.goto('/students');
        await page.waitForLoadState('networkidle');

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible({ timeout: 5000 });

        await page.locator('tbody tr a').first().click();
        await page.waitForLoadState('networkidle');

        await expect(page.locator('h1')).toBeVisible({ timeout: 5000 });
    });
});
