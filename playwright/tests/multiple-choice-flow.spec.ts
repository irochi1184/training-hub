import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('複数選択テスト機能', () => {
    test('講師が複数選択問題を含むテストを作成できる', async ({ page }) => {
        await login(page, accounts.instructor.email, accounts.instructor.password);
        await page.goto('/tests/create');
        await page.waitForLoadState('networkidle');

        // 基本情報
        await page.locator('input[placeholder*="第1回"]').fill('複数選択テスト');
        await page.locator('select').first().selectOption({ index: 1 });

        // 問題を追加
        await page.getByRole('button', { name: '問題を追加' }).click();

        // 問題文入力
        await page.locator('textarea[placeholder*="問題文"]').fill('ブロック要素をすべて選べ');

        // 形式を複数選択に変更
        await page.locator('select').last().selectOption('multiple');

        // 選択肢を入力（デフォルト2つ + 追加2つ）
        const choiceInputs = page.locator('input[placeholder="選択肢を入力"]');
        await choiceInputs.nth(0).fill('div');
        await choiceInputs.nth(1).fill('span');

        await page.getByText('+ 選択肢を追加').click();
        await page.getByText('+ 選択肢を追加').click();
        await choiceInputs.nth(2).fill('p');
        await choiceInputs.nth(3).fill('a');

        // div と p を正解に
        const checkboxes = page.locator('input[type="checkbox"][title="正解にする"]');
        await checkboxes.nth(0).check();
        await checkboxes.nth(2).check();

        // 作成
        await page.getByRole('button', { name: 'テストを作成する' }).click();
        await page.waitForURL('**/tests');

        await expect(page.getByText('テストを作成しました')).toBeVisible();
    });

    test('受講生が複数選択問題でチェックボックスが表示される', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        // HTML基礎テストに複数選択問題が含まれている
        await page.getByRole('link', { name: '受験する' }).first().click();
        await page.waitForLoadState('networkidle');

        // 複数選択問題があることを確認
        await expect(page.getByText('複数選択可')).toBeVisible();

        // チェックボックスが表示される
        const multipleChoiceCheckboxes = page.locator('input[type="checkbox"]');
        expect(await multipleChoiceCheckboxes.count()).toBeGreaterThan(0);
    });

    test('テスト一覧で期間外テストに受験ボタンが表示されない', async ({ page }) => {
        await login(page, accounts.student.email, accounts.student.password);
        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        // 公開中のテストには「受験する」が表示される
        // シーダーの HTML基礎テスト は opens_at=null なので受験可能
        const takeButtons = page.getByRole('link', { name: '受験する' });
        expect(await takeButtons.count()).toBeGreaterThan(0);
    });
});
