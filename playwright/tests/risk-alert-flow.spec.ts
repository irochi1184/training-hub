import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('要注意者アラートフロー', () => {
    test('instructor がログインして要注意者一覧を表示できる', async ({ page }) => {
        // ログインする
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // 要注意者一覧ページに遷移する
        await page.goto('/risk-alerts');

        // ページが正常に表示されることを確認する
        await expect(page).toHaveURL(/\/risk-alerts/);
        await expect(page.getByRole('heading')).toBeVisible();
    });

    test('instructor がアラートを解消できる', async ({ page }) => {
        // ログインする
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // 要注意者一覧ページに遷移する
        await page.goto('/risk-alerts');

        // 解消ボタンが存在する場合のみ操作する
        const resolveButton = page.getByRole('button', { name: '解消' }).first();
        const count = await resolveButton.count();

        if (count === 0) {
            // アラートがない場合はスキップする
            test.skip();
            return;
        }

        await resolveButton.click();

        // 成功メッセージが表示されることを確認する
        await expect(page.getByText('アラートを解消しました')).toBeVisible();
    });
});
