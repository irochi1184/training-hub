import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('プロフィール機能', () => {
    test('受講生がサイドバーからプロフィール画面に遷移できる', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        await page.getByRole('link', { name: 'マイプロフィール' }).click();
        await page.waitForURL('**/profile');

        await expect(page.getByRole('heading', { name: 'マイプロフィール' })).toBeVisible();
    });

    test('プロフィール設定済みの場合データが表示される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');

        // シーダーで受講生1号にはプロフィールが設定されている
        await expect(page.getByText('自己紹介')).toBeVisible();
        await expect(page.getByText('プログラミング初心者です')).toBeVisible();
    });

    test('受講生がプロフィールを編集して保存できる', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/profile/edit');
        await page.waitForLoadState('networkidle');

        // 自己紹介を入力
        await page.locator('textarea#bio').fill('Playwrightテスト用の自己紹介');

        // 学習目標を入力
        await page.locator('textarea#learning_goal').fill('E2Eテストをマスターする');

        // 既存スキルを全て削除してから新規追加
        const removeButtons = page.locator('button:has(svg path[d="M6 18L18 6M6 6l12 12"])');
        const count = await removeButtons.count();
        for (let i = count - 1; i >= 0; i--) {
            await removeButtons.nth(i).click();
        }

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
        await login(page, accounts.admin.email, accounts.admin.password);

        // 受講生一覧から受講生1号の詳細へ
        await page.goto('/students');
        await page.waitForLoadState('networkidle');
        await page.getByRole('link', { name: '受講生 1号' }).click();
        await page.waitForLoadState('networkidle');

        // プロフィールタブをクリック
        await page.getByRole('button', { name: 'プロフィール' }).click();

        // プロフィール内容が表示される
        await expect(page.getByRole('heading', { name: '自己紹介' })).toBeVisible();
        await expect(page.getByRole('heading', { name: '学習目標' })).toBeVisible();
    });

    test('admin/instructorがプロフィールページにアクセスすると403', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        const response = await page.goto('/profile');
        expect(response?.status()).toBe(403);
    });
});
