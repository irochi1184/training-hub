# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: tests/enrollment-flow.spec.ts >> エンロールメント管理 >> admin が受講登録を解除できる
- Location: tests/enrollment-flow.spec.ts:57:5

# Error details

```
Error: page.goto: Protocol error (Page.navigate): Cannot navigate to invalid URL
Call log:
  - navigating to "/login", waiting until "load"

```

# Test source

```ts
  1  | import type { Page } from '@playwright/test';
  2  | 
  3  | /**
  4  |  * ログインヘルパー
  5  |  * メールアドレスとパスワードでログインし、ダッシュボードに遷移するまで待つ
  6  |  */
  7  | export async function login(page: Page, email: string, password: string): Promise<void> {
> 8  |     await page.goto('/login');
     |                ^ Error: page.goto: Protocol error (Page.navigate): Cannot navigate to invalid URL
  9  |     await page.getByLabel('メールアドレス').fill(email);
  10 |     await page.getByLabel('パスワード').fill(password);
  11 |     await page.getByRole('button', { name: 'ログイン' }).click();
  12 |     // ダッシュボードへの遷移を待つ
  13 |     await page.waitForURL('**/dashboard');
  14 | }
  15 | 
  16 | /**
  17 |  * ログアウトヘルパー
  18 |  */
  19 | export async function logout(page: Page): Promise<void> {
  20 |     await page.getByRole('button', { name: 'ログアウト' }).click();
  21 |     await page.waitForURL('**/login');
  22 | }
  23 | 
  24 | /** テスト用アカウント情報 */
  25 | export const accounts = {
  26 |     admin: {
  27 |         email: 'admin@example.com',
  28 |         password: 'password',
  29 |     },
  30 |     instructor: {
  31 |         email: 'instructor@example.com',
  32 |         password: 'password',
  33 |     },
  34 |     student: {
  35 |         email: 'student@example.com',
  36 |         password: 'password',
  37 |     },
  38 | } as const;
  39 | 
```