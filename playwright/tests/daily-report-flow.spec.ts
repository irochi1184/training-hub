import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('日報提出フロー', () => {
    test('student がログインして日報を提出すると成功メッセージが表示される', async ({ page }) => {
        // ログインする
        await login(page, accounts.student.email, accounts.student.password);

        // 日報入力ページに遷移する
        await page.goto('/daily-reports/create');

        // コホートを選択する（最初の選択肢を選ぶ）
        const cohortSelect = page.getByLabel('コホート');
        await cohortSelect.selectOption({ index: 0 });

        // 提出日を入力する
        const today = new Date().toISOString().split('T')[0];
        await page.getByLabel('提出日').fill(today);

        // 理解度を選択する
        await page.getByLabel('理解度').selectOption('3');

        // 内容を入力する
        await page.getByLabel('今日の内容').fill('今日はPHPの変数とデータ型について学びました。');

        // 提出ボタンをクリックする
        await page.getByRole('button', { name: '提出' }).click();

        // 成功メッセージが表示されることを確認する
        await expect(page.getByText('日報を提出しました')).toBeVisible();
    });
});
