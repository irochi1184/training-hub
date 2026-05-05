import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('テスト分析フロー', () => {
    test('instructor がテスト分析画面を閲覧できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        // HTML基礎テストの行にある「分析」リンクをクリック
        const testRow = page.locator('tr', { hasText: 'HTML基礎テスト' });
        const analyticsLink = testRow.getByRole('link', { name: '分析' });
        await expect(analyticsLink).toBeVisible({ timeout: 5000 });
        await analyticsLink.click();
        await page.waitForLoadState('networkidle');

        // 分析画面のサマリーが表示される
        await expect(page.getByText('受験者数')).toBeVisible({ timeout: 5000 });
        await expect(page.getByText('平均点')).toBeVisible();
        await expect(page.getByText('最高点')).toBeVisible();
        await expect(page.getByText('最低点')).toBeVisible();

        // 問題別正答率セクション
        await expect(page.getByText('問題別正答率')).toBeVisible();
        await expect(page.getByText('第1問')).toBeVisible();

        // 受験者一覧
        await expect(page.getByText('受験者一覧')).toBeVisible();
    });

    test('admin がテスト分析画面を閲覧できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        const testRow = page.locator('tr', { hasText: 'HTML基礎テスト' });
        const analyticsLink = testRow.getByRole('link', { name: '分析' });
        await expect(analyticsLink).toBeVisible({ timeout: 5000 });
        await analyticsLink.click();
        await page.waitForLoadState('networkidle');

        await expect(page.getByText('問題別正答率')).toBeVisible({ timeout: 5000 });
    });

    test('受験者名をクリックすると回答詳細に遷移できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        const testRow = page.locator('tr', { hasText: 'HTML基礎テスト' });
        await testRow.getByRole('link', { name: '分析' }).click();
        await page.waitForLoadState('networkidle');

        // 受験者一覧から最初の受験者リンクをクリック
        const submissionLink = page.locator('section').last().locator('tbody tr a').first();
        await expect(submissionLink).toBeVisible({ timeout: 5000 });
        await submissionLink.click();
        await page.waitForLoadState('networkidle');

        // 回答詳細ページに遷移
        await expect(page.getByText('問題別の結果')).toBeVisible({ timeout: 5000 });
    });
});
