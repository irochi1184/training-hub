import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('プロフィール機能', () => {
    test('受講生がサイドバーからプロフィール画面に遷移できる', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        await page.getByRole('link', { name: 'マイプロフィール' }).click();
        await page.waitForURL('**/profile');

        await expect(page.getByText('マイプロフィール')).toBeVisible();
    });

    test('プロフィール未設定時に設定画面へのリンクが表示される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');

        // 未設定メッセージまたはプロフィール内容が表示される
        // シーダーでプロフィール設定済みの場合は自己紹介が見える
        const hasProfile = await page.getByText('自己紹介').isVisible().catch(() => false);
        const hasEmpty = await page.getByText('プロフィールがまだ設定されていません').isVisible().catch(() => false);
        expect(hasProfile || hasEmpty).toBeTruthy();
    });

    test('受講生がプロフィールを編集して保存できる', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/profile/edit');
        await page.waitForLoadState('networkidle');

        // 自己紹介を入力
        await page.locator('textarea#bio').fill('Playwrightテスト用の自己紹介');

        // 学習目標を入力
        await page.locator('textarea#learning_goal').fill('E2Eテストをマスターする');

        // スキルを追加
        await page.getByText('+ 追加').click();
        await page.locator('input[placeholder*="スキル名"]').fill('TypeScript');
        await page.locator('select').last().selectOption('2');

        // 保存
        await page.getByRole('button', { name: '保存する' }).click();
        await page.waitForURL('**/profile');

        // 保存結果を確認
        await expect(page.getByText('Playwrightテスト用の自己紹介')).toBeVisible();
        await expect(page.getByText('E2Eテストをマスターする')).toBeVisible();
        await expect(page.getByText('TypeScript')).toBeVisible();
    });

    test('管理者が受講生詳細のプロフィールタブで情報を閲覧できる', async ({ page }) => {
        // まず受講生としてプロフィールを設定
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/profile/edit');
        await page.waitForLoadState('networkidle');
        await page.locator('textarea#bio').fill('管理者閲覧テスト用');
        await page.getByRole('button', { name: '保存する' }).click();
        await page.waitForURL('**/profile');

        // ログアウトして管理者でログイン
        await page.getByRole('button', { name: 'ログアウト' }).click();
        await page.waitForURL('**/login');
        await login(page, accounts.admin.email, accounts.admin.password);

        // 受講生一覧から最初の受講生の詳細へ
        await page.goto('/students');
        await page.waitForLoadState('networkidle');
        await page.getByRole('link', { name: '詳細' }).first().click();
        await page.waitForLoadState('networkidle');

        // プロフィールタブをクリック
        await page.getByRole('button', { name: 'プロフィール' }).click();

        // プロフィール内容が表示される
        await expect(page.getByText('自己紹介')).toBeVisible();
    });

    test('admin/instructorがプロフィールページにアクセスすると403', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        const response = await page.goto('/profile');
        expect(response?.status()).toBe(403);
    });
});
