import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('日報提出フロー', () => {
    test('student がログインして日報を提出すると成功メッセージが表示される', async ({ page }) => {
        // ログインする
        await login(page, accounts.student.email, accounts.student.password);

        // 日報入力ページに遷移する
        await page.goto('/daily-reports/create');
        await page.waitForLoadState('networkidle');

        // カリキュラムを選択する（index 1 = 最初の実際の選択肢。index 0 はプレースホルダー）
        const curriculumSelect = page.locator('select#curriculum_id');
        await curriculumSelect.selectOption({ index: 1 });

        // 日付を入力する（ラベルは「日付」）
        const today = new Date().toISOString().split('T')[0];
        await page.locator('input#reported_on').fill(today);

        // 理解度を選択する（ラジオボタン、value=3 を選択）
        await page.locator('input[type="radio"][value="3"]').check();

        // 学習内容を入力する（ラベルは「学習内容」）
        await page.locator('textarea#content').fill('今日はPHPの変数とデータ型について学びました。');

        // 提出ボタンをクリックする
        await page.getByRole('button', { name: '日報を提出する' }).click();

        // 成功メッセージが表示されることを確認する
        await expect(page.getByText('日報を提出しました')).toBeVisible({ timeout: 10000 });
    });
});
