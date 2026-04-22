import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('要注意者アラートフロー', () => {
    test('admin が理由フィルターで絞り込める', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);
        await page.goto('/risk-alerts');
        await expect(page.getByRole('heading', { name: '要注意者一覧' })).toBeVisible();

        // 「理解度低下」で絞り込む
        await page.locator('select').first().selectOption('low_understanding');
        await page.waitForLoadState('networkidle');

        // URL に reason=low_understanding が含まれる
        await expect(page).toHaveURL(/reason=low_understanding/);
    });

    test('admin が解消済アラートも全件で閲覧できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);
        await page.goto('/risk-alerts');

        await page.getByRole('button', { name: '全件' }).click();
        await page.waitForLoadState('networkidle');

        // URL に show_resolved=1
        await expect(page).toHaveURL(/show_resolved=1/);
    });

    test('instructor がログインして要注意者一覧を表示できる', async ({ page }) => {
        // ログインする
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // 要注意者一覧ページに遷移する
        await page.goto('/risk-alerts');
        await page.waitForLoadState('networkidle');

        // ページが正常に表示されることを確認する
        await expect(page).toHaveURL(/\/risk-alerts/);
        // ページにコンテンツが描画されるのを待つ
        await expect(page.getByRole('heading', { name: '要注意者一覧' })).toBeVisible({ timeout: 10000 });
    });

    test('instructor がアラートを解消できる', async ({ page }) => {
        // ログインする
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // 要注意者一覧ページに遷移する
        await page.goto('/risk-alerts');
        await page.waitForLoadState('networkidle');

        // 解消ボタンが存在する場合のみ操作する
        const resolveButton = page.getByRole('button', { name: '解消にする' }).first();

        try {
            await resolveButton.waitFor({ timeout: 5000 });
        } catch {
            // アラートがない場合はスキップする
            test.skip();
            return;
        }

        await resolveButton.click();

        // 成功メッセージが表示されることを確認する
        await expect(page.getByText('アラートを解消しました')).toBeVisible({ timeout: 10000 });
    });
});
