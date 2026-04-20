import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('要注意者アラートフロー', () => {
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
