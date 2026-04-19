import type { Page } from '@playwright/test';

/**
 * ログインヘルパー
 * メールアドレスとパスワードでログインし、ダッシュボードに遷移するまで待つ
 */
export async function login(page: Page, email: string, password: string): Promise<void> {
    await page.goto('/login');
    await page.getByLabel('メールアドレス').fill(email);
    await page.getByLabel('パスワード').fill(password);
    await page.getByRole('button', { name: 'ログイン' }).click();
    // ダッシュボードへの遷移を待つ
    await page.waitForURL('**/dashboard');
}

/**
 * ログアウトヘルパー
 */
export async function logout(page: Page): Promise<void> {
    await page.getByRole('button', { name: 'ログアウト' }).click();
    await page.waitForURL('**/login');
}

/** テスト用アカウント情報 */
export const accounts = {
    admin: {
        email: 'admin@example.com',
        password: 'password',
    },
    instructor: {
        email: 'instructor@example.com',
        password: 'password',
    },
    student: {
        email: 'student@example.com',
        password: 'password',
    },
} as const;
