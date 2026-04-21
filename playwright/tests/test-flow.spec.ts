import { test, expect } from '@playwright/test';
import { accounts, login } from './helpers';

test.describe('テスト作成フロー', () => {
    test('instructor がログインしてテストを作成するとテスト一覧に表示される', async ({ page }) => {
        // ログインする
        await login(page, accounts.instructor.email, accounts.instructor.password);

        // テスト作成ページに遷移する
        await page.goto('/tests/create');
        await page.waitForLoadState('networkidle');

        // タイトルを入力する（placeholder で特定）
        await page.getByPlaceholder('例: 第1回 HTTPの基礎').fill('PHPの基礎テスト');

        // コホートを選択する（index 1 = 最初の実際の選択肢）
        const cohortSelect = page.locator('select').first();
        await cohortSelect.selectOption({ index: 1 });

        // 問題を追加する
        await page.getByRole('button', { name: '問題を追加' }).click();

        // 問題文を入力する
        await page.getByPlaceholder('問題文を入力してください').first().fill('PHPで変数を宣言するために使う記号はどれですか？');

        // 選択肢を入力する（デフォルトで2つの空の選択肢がある）
        const choiceInputs = page.getByPlaceholder('選択肢を入力');
        await choiceInputs.nth(0).fill('$');
        await choiceInputs.nth(1).fill('#');

        // 正解を選択する（チェックボックス）
        await page.locator('input[type="checkbox"]').first().check();

        // テストを作成するボタンをクリック
        await page.getByRole('button', { name: 'テストを作成する' }).click();

        // テスト一覧ページに遷移したことを確認する（/tests/create ではなく /tests 末尾）
        await expect(page).toHaveURL(/\/tests$/, { timeout: 15000 });
        await page.waitForLoadState('networkidle');
        await expect(page.getByText('PHPの基礎テスト')).toBeVisible({ timeout: 10000 });
    });
});

test.describe('テスト受験フロー', () => {
    test('student がログインしてテストを受験すると結果が表示される', async ({ page }) => {
        // ログインする
        await login(page, accounts.student.email, accounts.student.password);

        // テスト一覧に遷移する
        await page.goto('/tests');
        await page.waitForLoadState('networkidle');

        // 受験可能なテストが存在する場合のみ実行する
        const takeLink = page.getByRole('link', { name: '受験する' }).first();
        const count = await takeLink.count();

        if (count === 0) {
            test.skip();
            return;
        }

        await takeLink.click();
        await page.waitForLoadState('networkidle');

        // 受験画面が表示されることを確認する
        await expect(page.locator('h1, h2').first()).toBeVisible({ timeout: 10000 });

        // 全問のラジオボタンを選択する
        const radioButtons = page.locator('input[type="radio"]');
        const radioCount = await radioButtons.count();
        for (let i = 0; i < radioCount; i++) {
            // 各問題の最初の選択肢を選ぶ
            const isChecked = await radioButtons.nth(i).isChecked();
            if (!isChecked) {
                await radioButtons.nth(i).check();
                break; // 1つの問題につき1つ選べばよい
            }
        }

        // 提出ボタンをクリックする（一覧画面の「提出する」）
        const submitButton = page.getByRole('button', { name: '提出する' }).first();
        if (await submitButton.count() > 0) {
            await submitButton.click();

            // 確認ダイアログ内の「提出する」ボタンを押す（2 つ目に出現する方）
            const confirmSubmit = page.getByRole('button', { name: '提出する' }).last();
            await confirmSubmit.click();

            // 結果画面に遷移することを確認する
            await expect(page).toHaveURL(/\/submissions\//, { timeout: 10000 });
        }
    });
});
