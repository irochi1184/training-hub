import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('エンロールメント管理', () => {
    test('admin がエンロールメント管理画面を表示できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.getByRole('link', { name: '受講生登録' }).click();
        await page.waitForURL('**/enrollments*');

        await expect(page.getByRole('heading', { name: '受講生登録管理' })).toBeVisible();
        await expect(page.locator('select').first()).toBeVisible();
    });

    test('admin が受講生を個別登録できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        // ロジック研修に受講生を追加（受講生4号、5号のみ登録されている）
        await page.goto('/enrollments?curriculum_id=2');
        await page.waitForLoadState('networkidle');

        // 追加可能な受講生がいれば登録
        const addSelect = page.locator('select').nth(1);
        const optionCount = await addSelect.locator('option').count();

        if (optionCount > 1) {
            await addSelect.selectOption({ index: 1 });
            await page.getByRole('button', { name: '登録', exact: true }).click();
            await page.waitForLoadState('networkidle');

            await expect(page.getByText('受講生を登録しました')).toBeVisible();
        }
    });

    test('admin が一括登録できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);
        await page.goto('/enrollments');
        await page.waitForLoadState('networkidle');

        // 一括登録ボタンをクリック
        await page.getByRole('button', { name: '一括登録' }).click();

        // モーダルが表示される
        await expect(page.getByRole('heading', { name: '一括登録' })).toBeVisible();

        // メールアドレスを入力
        await page.locator('textarea').fill('student4@example.com\nstudent5@example.com');

        // 一括登録実行
        await page.getByRole('button', { name: '一括登録する' }).click();
        await page.waitForLoadState('networkidle');

        // 成功メッセージ
        await expect(page.getByText('名を登録しました')).toBeVisible();
    });

    test('admin が受講登録を解除できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);
        await page.goto('/enrollments');
        await page.waitForLoadState('networkidle');

        // 解除ボタンをクリック（confirm ダイアログを自動承認）
        page.on('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: '解除' }).first().click();
        await page.waitForLoadState('networkidle');

        await expect(page.getByText('受講登録を解除しました')).toBeVisible();
    });

    test('instructor がサイドバーから受講生登録にアクセスできる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.getByRole('link', { name: '受講生登録' }).click();
        await page.waitForURL('**/enrollments*');

        await expect(page.getByRole('heading', { name: '受講生登録管理' })).toBeVisible();
    });
});
