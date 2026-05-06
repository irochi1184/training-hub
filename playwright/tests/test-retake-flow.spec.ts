import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('テスト再受験管理', () => {
    test('再受験可能テストを受験し、再受験ボタンが表示される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        // テスト一覧へ
        await page.getByRole('link', { name: 'テスト一覧' }).click();
        await page.waitForURL('**/tests*');

        // CSS基礎テスト（再受験可）の受験ボタンをクリック
        const row = page.locator('tr', { hasText: 'CSS基礎テスト（再受験可）' });
        await row.getByRole('link', { name: '受験する' }).click();

        // 受験画面表示
        await expect(page.getByText('CSSでテキストの色を変えるプロパティはどれですか？')).toBeVisible();

        // 間違った回答を選択して提出
        await page.getByRole('radio', { name: 'text-color' }).click();
        await page.getByRole('button', { name: '提出する' }).first().click();
        // 確認ダイアログの提出ボタン
        await page.getByRole('button', { name: '提出する' }).last().click();
        await page.waitForURL('**/submissions/**');

        // 結果画面に遷移
        await expect(page.getByText('第1回目')).toBeVisible();

        // テスト一覧に戻る
        await page.getByRole('link', { name: 'テスト一覧に戻る' }).click();
        await page.waitForURL('**/tests*');

        // 再受験ボタンが表示される
        const row2 = page.locator('tr', { hasText: 'CSS基礎テスト（再受験可）' });
        await expect(row2.getByRole('link', { name: '再受験' })).toBeVisible();

        // 成績情報が表示されている（受験回数）
        await expect(row2.getByText('1回目')).toBeVisible();
    });

    test('再受験して最高点が更新される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);

        // テスト一覧へ（テスト1で1回目受験済みなので「再受験」が見える）
        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        // 再受験
        const row = page.locator('tr', { hasText: 'CSS基礎テスト（再受験可）' });
        await row.getByRole('link', { name: '再受験' }).click();

        // 正解を選択して提出
        await page.getByRole('radio', { name: 'color', exact: true }).click();
        await page.getByRole('button', { name: '提出する' }).first().click();
        await page.getByRole('button', { name: '提出する' }).last().click();
        await page.waitForURL('**/submissions/**');

        // 結果画面: 第2回目、受験履歴が表示される
        await expect(page.getByText('第2回目', { exact: true })).toBeVisible();
        await expect(page.getByText('受験履歴（最高点: 1点）')).toBeVisible();
    });

    test('テスト作成画面で受験回数上限を設定できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);

        await page.goto('/tests/create');
        await page.waitForLoadState('networkidle');

        // 受験回数上限の入力フィールドが存在する
        const maxAttemptsInput = page.locator('input[type="number"][min="0"]');
        await expect(maxAttemptsInput).toBeVisible();

        // 値を入力できる
        await maxAttemptsInput.fill('5');
        await expect(maxAttemptsInput).toHaveValue('5');
    });
});
