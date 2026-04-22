import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('CSV出力フロー', () => {
    test('admin が日報CSVをダウンロードできる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/exports');
        await expect(page.getByRole('heading', { name: 'CSV出力' })).toBeVisible();

        // コホート選択 (cohort_id は required)
        // 日報CSVブロックの最初の select がコホート
        const cohortSelect = page.locator('select').first();
        const cohortOptions = cohortSelect.locator('option[value]:not([value=""])');
        if ((await cohortOptions.count()) === 0) {
            test.skip();
            return;
        }
        const firstCohortValue = await cohortOptions.first().getAttribute('value');
        if (!firstCohortValue) {
            test.skip();
            return;
        }
        await cohortSelect.selectOption(firstCohortValue);

        // 日報CSV ダウンロードリンクをクリックし、download イベントを待つ
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('link', { name: /日報CSV/ }).click();
        const download = await downloadPromise;

        // ファイル名が CSV 拡張子であること
        expect(download.suggestedFilename()).toMatch(/\.csv$/i);
    });

    test('admin がテスト結果CSVをダウンロードできる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/exports');

        // 最初のテストを選択する
        const testSelect = page.locator('select').filter({ hasText: 'テストを選択' });
        const options = testSelect.locator('option:not([disabled])');
        const optionCount = await options.count();

        if (optionCount === 0) {
            test.skip();
            return;
        }

        const firstValue = await options.first().getAttribute('value');
        if (firstValue === null) {
            test.skip();
            return;
        }
        await testSelect.selectOption(firstValue);

        // テスト結果CSV ダウンロードリンクをクリック
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('link', { name: /テスト結果CSV/ }).click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.csv$/i);
    });

    test('student は CSV出力ページにアクセスできない', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        const response = await page.goto('/exports');
        // 403 が返るか、ダッシュボードへリダイレクトされる
        expect(response?.status()).toBe(403);
    });
});
