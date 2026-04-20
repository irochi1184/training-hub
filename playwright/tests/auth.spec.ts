import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('認証', () => {
    test('ログイン画面が表示される', async ({ page }) => {
        await page.goto('/login');

        // ログインフォームが存在することを確認する
        await expect(page.getByLabel('メールアドレス')).toBeVisible();
        await expect(page.getByLabel('パスワード')).toBeVisible();
        await expect(page.getByRole('button', { name: 'ログイン' })).toBeVisible();
    });

    test('ログインするとダッシュボードに遷移する', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        // ダッシュボードに遷移していることを確認する
        await expect(page).toHaveURL(/\/dashboard/);
    });

    test('ログアウトするとログイン画面に戻る', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        // ログアウトボタンをクリックする
        await page.getByRole('button', { name: 'ログアウト' }).click();

        // ログイン画面に戻ることを確認する
        await expect(page).toHaveURL(/\/login/);
    });
});
