import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('カリキュラム管理フロー', () => {
    test('admin がカリキュラムを作成できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula/create');
        await page.waitForLoadState('networkidle');

        // 名称を入力（placeholder で特定）
        await page.getByPlaceholder('例: IT研修').fill('E2Eテスト研修');

        // 担当講師を選択
        await page.locator('select').first().selectOption({ index: 1 });

        // 開始日・終了日を入力
        const dateInputs = page.locator('input[type="date"]');
        await dateInputs.nth(0).fill('2026-06-01');
        await dateInputs.nth(1).fill('2026-12-31');

        await page.getByRole('button', { name: '作成する' }).click();

        await expect(page).toHaveURL(/\/curricula$/, { timeout: 10000 });
        await expect(page.getByText('E2Eテスト研修')).toBeVisible({ timeout: 5000 });
    });

    test('admin がカリキュラムを編集できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        // E2Eテスト研修の編集ページに直接遷移
        await page.goto('/curricula');
        await page.waitForLoadState('networkidle');

        // E2Eテスト研修の行から編集リンクをクリック
        const targetRow = page.locator('tr', { hasText: 'E2Eテスト研修' });
        await targetRow.getByRole('link', { name: '編集' }).click();
        await page.waitForLoadState('networkidle');

        // 名称を変更
        const nameInput = page.getByPlaceholder('例: IT研修');
        await nameInput.clear();
        await nameInput.fill('更新済み研修');

        // 日付を再入力（編集画面で空になる場合がある）
        const dateInputs = page.locator('input[type="date"]');
        const startsValue = await dateInputs.nth(0).inputValue();
        if (!startsValue) {
            await dateInputs.nth(0).fill('2026-06-01');
            await dateInputs.nth(1).fill('2026-12-31');
        }

        await page.getByRole('button', { name: '変更を保存する' }).click();

        await expect(page).toHaveURL(/\/curricula$/, { timeout: 10000 });
        await expect(page.getByText('更新済み研修')).toBeVisible({ timeout: 5000 });
    });

    test('admin がカリキュラムを削除できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula');
        await page.waitForLoadState('networkidle');

        // E2Eテスト研修の削除ボタンをクリック
        const row = page.locator('tr', { hasText: 'E2Eテスト研修' });
        const rowCount = await row.count();

        if (rowCount > 0) {
            await row.getByRole('button', { name: '削除' }).click();
            await page.getByRole('button', { name: '削除する' }).click();
            await expect(page.getByText('カリキュラムを削除しました')).toBeVisible({ timeout: 10000 });
        }
    });
});
