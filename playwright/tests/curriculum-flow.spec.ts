import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('カリキュラム管理フロー', () => {
    test('admin がメイン講師を選択してカリキュラムを作成できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula/create');
        await page.waitForLoadState('networkidle');

        await page.getByPlaceholder('例: IT研修').fill('E2Eテスト研修');

        // メイン講師を選択（最初の講師）
        const mainSelect = page.locator('select').nth(0);
        const mainOptions = mainSelect.locator('option');
        const firstOptionValue = await mainOptions.nth(0).getAttribute('value');
        await mainSelect.selectOption(firstOptionValue!);

        // 開始日・終了日
        const dateInputs = page.locator('input[type="date"]');
        await dateInputs.nth(0).fill('2026-06-01');
        await dateInputs.nth(1).fill('2026-12-31');

        await page.getByRole('button', { name: '作成する' }).click();

        await expect(page).toHaveURL(/\/curricula$/, { timeout: 10000 });
        await expect(page.getByText('E2Eテスト研修')).toBeVisible({ timeout: 5000 });
    });

    test('admin がメイン講師とサブ講師を同時に設定できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula/create');
        await page.waitForLoadState('networkidle');

        await page.getByPlaceholder('例: IT研修').fill('講師複数テスト');

        // メイン講師を選択（1人目）
        const mainSelect = page.locator('select').nth(0);
        const mainOptions = mainSelect.locator('option');
        const firstValue = await mainOptions.nth(0).getAttribute('value');
        await mainSelect.selectOption(firstValue!);

        // サブ講師を選択（メイン講師に選ばれていない講師が表示される）
        const subSelect = page.locator('select').nth(1);
        const subOptionCount = await subSelect.locator('option').count();
        if (subOptionCount > 0) {
            const subValue = await subSelect.locator('option').nth(0).getAttribute('value');
            await subSelect.selectOption(subValue!);
        }

        const dateInputs = page.locator('input[type="date"]');
        await dateInputs.nth(0).fill('2026-06-01');
        await dateInputs.nth(1).fill('2026-12-31');

        await page.getByRole('button', { name: '作成する' }).click();

        await expect(page).toHaveURL(/\/curricula$/, { timeout: 10000 });
        await expect(page.getByText('講師複数テスト')).toBeVisible({ timeout: 5000 });

        // 一覧でメイン・サブ表示を確認
        const row = page.locator('tr', { hasText: '講師複数テスト' });
        await expect(row.getByText('メイン:')).toBeVisible();
        if (subOptionCount > 0) {
            await expect(row.getByText('サブ:')).toBeVisible();
        }
    });

    test('admin がカリキュラムを編集できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula');
        await page.waitForLoadState('networkidle');

        // IT研修の編集ページへ
        const targetRow = page.locator('tr', { hasText: 'IT研修' });
        await targetRow.getByRole('link', { name: '編集' }).click();
        await page.waitForLoadState('networkidle');

        // メイン講師のselectに既存値が選択されていることを確認
        const mainSelect = page.locator('select').nth(0);
        const selectedValues = await mainSelect.evaluate((el: HTMLSelectElement) =>
            Array.from(el.selectedOptions).map(o => o.value)
        );
        expect(selectedValues.length).toBeGreaterThan(0);

        // 名称を変更して保存
        const nameInput = page.getByPlaceholder('例: IT研修');
        await nameInput.clear();
        await nameInput.fill('更新済み研修');

        await page.getByRole('button', { name: '変更を保存する' }).click();

        await expect(page).toHaveURL(/\/curricula$/, { timeout: 10000 });
        await expect(page.getByText('更新済み研修')).toBeVisible({ timeout: 5000 });
    });

    test('一覧画面でメイン講師・サブ講師が表示される', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula');
        await page.waitForLoadState('networkidle');

        // Seederで作成されたIT研修にはメイン講師・サブ講師が設定されている
        const itRow = page.locator('tr', { hasText: /IT研修|更新済み研修/ });
        await expect(itRow.getByText('メイン:')).toBeVisible();
        await expect(itRow.getByText('サブ:')).toBeVisible();
    });

    test('admin がカリキュラムを削除できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.goto('/curricula');
        await page.waitForLoadState('networkidle');

        // E2Eテスト研修の削除
        const row = page.locator('tr', { hasText: 'E2Eテスト研修' });
        const rowCount = await row.count();

        if (rowCount > 0) {
            await row.getByRole('button', { name: '削除' }).click();
            await page.getByRole('button', { name: '削除する' }).click();
            await expect(page.getByText('カリキュラムを削除しました')).toBeVisible({ timeout: 10000 });
        }
    });
});
