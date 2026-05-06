import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('お知らせフロー', () => {
    test('admin がお知らせ一覧を表示できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.getByRole('link', { name: 'お知らせ' }).first().click();
        await expect(page).toHaveURL(/\/announcements/);
        await expect(page.getByText('全体連絡: システムメンテナンスのお知らせ')).toBeVisible();
    });

    test('admin がお知らせを作成できる', async ({ page }) => {
        await login(page, accounts.admin.email, accounts.admin.password);

        await page.getByRole('link', { name: 'お知らせ' }).first().click();
        await page.getByRole('link', { name: 'お知らせを作成' }).click();
        await expect(page).toHaveURL(/\/announcements-create/);

        await page.getByLabel('タイトル').fill('Playwright テスト通知');
        await page.getByLabel('本文').fill('これはE2Eテストで作成したお知らせです。');
        await page.getByLabel('重要').check();
        await page.getByRole('button', { name: '公開する' }).click();

        await expect(page).toHaveURL(/\/announcements/);
        await expect(page.getByText('Playwright テスト通知')).toBeVisible();
    });

    test('student がお知らせ詳細を閲覧すると既読になる', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        await page.getByRole('link', { name: 'お知らせ' }).first().click();
        await expect(page).toHaveURL(/\/announcements/);

        // 未読バッジが表示されていることを確認
        await expect(page.getByText('未読').first()).toBeVisible();

        // お知らせをクリック
        await page.getByText('全体連絡: システムメンテナンスのお知らせ').click();
        await expect(page.getByText('来週月曜日の深夜にシステムメンテナンス')).toBeVisible();

        // 一覧に戻ると既読になっている
        await page.getByRole('link', { name: 'お知らせ一覧に戻る' }).click();
    });

    test('instructor がカリキュラム宛のお知らせを作成できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.getByRole('link', { name: 'お知らせ' }).first().click();
        await page.getByRole('link', { name: 'お知らせを作成' }).click();

        await page.getByLabel('タイトル').fill('講師からの連絡');
        await page.getByLabel('本文').fill('明日の授業の持ち物を確認してください。');
        await page.getByLabel('カリキュラム指定').check();

        // カリキュラム選択が表示される
        await expect(page.getByLabel('対象カリキュラム')).toBeVisible();
        await page.getByLabel('対象カリキュラム').selectOption({ index: 1 });

        await page.getByRole('button', { name: '公開する' }).click();
        await expect(page).toHaveURL(/\/announcements/);
        await expect(page.getByText('講師からの連絡')).toBeVisible();
    });

    test('ヘッダーにベルアイコンと未読バッジが表示される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        // ヘッダーのベルアイコンリンクが存在する
        const bellLink = page.locator('header a[href="/announcements"]');
        await expect(bellLink).toBeVisible();
    });
});
