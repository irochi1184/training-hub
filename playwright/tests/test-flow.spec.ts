import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('テスト作成フロー', () => {
    test('instructor がログインしてテストを作成するとテスト一覧に表示される', async ({ page }) => {
        // ログインする
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // テスト作成ページに遷移する
        await page.goto('/tests/create');

        // テスト名を入力する
        await page.getByLabel('テスト名').fill('PHPの基礎テスト');

        // コホートを選択する（最初の選択肢を選ぶ）
        await page.getByLabel('コホート').selectOption({ index: 0 });

        // 問題を追加する
        await page.getByRole('button', { name: '問題を追加' }).click();

        // 問題文を入力する
        await page.getByLabel('問題文').first().fill('PHPで変数を宣言するために使う記号はどれですか？');

        // 選択肢を入力する
        const choiceInputs = page.getByPlaceholder('選択肢');
        await choiceInputs.nth(0).fill('$');
        await choiceInputs.nth(1).fill('#');

        // 正解を選択する
        await page.getByRole('radio').first().check();

        // 保存ボタンをクリックする
        await page.getByRole('button', { name: '保存' }).click();

        // テスト一覧ページに遷移したことを確認する
        await expect(page).toHaveURL(/\/tests/);
        await expect(page.getByText('PHPの基礎テスト')).toBeVisible();
    });
});

test.describe('テスト受験フロー', () => {
    test('student がログインしてテストを受験すると結果が表示される', async ({ page }) => {
        // ログインする
        await login(page, accounts.student.email, accounts.student.password);

        // テスト一覧に遷移する
        await page.goto('/tests');

        // 受験可能なテストが存在する場合のみ実行する
        const takeButton = page.getByRole('link', { name: '受験する' }).first();
        const count = await takeButton.count();

        if (count === 0) {
            test.skip();
            return;
        }

        await takeButton.click();

        // 受験画面が表示されることを確認する
        await expect(page.getByRole('heading', { name: /テスト/ })).toBeVisible();

        // 最初の問題の選択肢を選ぶ
        await page.getByRole('radio').first().check();

        // 提出ボタンをクリックする
        await page.getByRole('button', { name: '提出' }).click();

        // 結果画面に遷移することを確認する
        await expect(page).toHaveURL(/\/submissions\//);
        await expect(page.getByText('回答を提出しました')).toBeVisible();
    });
});
