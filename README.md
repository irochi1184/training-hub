# Training Hub

企業研修・スクール向けの受講管理アプリ。講師や運営担当が受講生の日報・理解度・テスト結果を一元管理し、状況を素早く把握できるようにする。

## 主な機能

- 受講生管理(一覧・詳細・理解度推移)
- 日報提出と講師コメント
- 小テスト作成・受験・自動採点
- 要注意者検知(3日未提出 / 理解度平均2.0以下 / 得点率50%以下)
- CSV出力(日報・テスト結果、BOM付きUTF-8)

## 技術スタック

- Backend: Laravel 13 / PHP 8.4
- Frontend: Inertia + Vue 3 + TypeScript + Tailwind CSS
- DB: MySQL 8(Docker利用時) または SQLite(ローカル実行時)
- Queue: Redis(Docker利用時)
- Test: PHPUnit(Feature) + Playwright(E2E)
- Dev: Docker (Laravel Sail)

## セットアップ

### パターン A: ローカル実行(SQLite)

PHP 8.4 / Composer / Node.js が入っていれば最も手早く起動できる。

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm run build
php artisan serve --port=8000
```

ブラウザで `http://localhost:8000` を開く。

### パターン B: Docker (Laravel Sail / MySQL)

```bash
composer install
cp .env.example .env
php artisan key:generate
# .env の DB_CONNECTION を mysql に切り替え
# DB_HOST=mysql, DB_DATABASE=training_hub, DB_USERNAME=sail, DB_PASSWORD=password
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

ブラウザで `http://localhost` を開く。

## Seed アカウント

`php artisan migrate:fresh --seed` 後、以下のアカウントが利用可能。パスワードは全て `password`。

| ロール | メールアドレス | 備考 |
| --- | --- | --- |
| admin | admin@example.com | 管理者 太郎 |
| instructor | instructor@example.com | 講師 花子(2024年4月期 担当) |
| instructor | instructor2@example.com | 講師 次郎(2024年10月期 担当) |
| student | student@example.com | 受講生 1号(4月期) |
| student | student2@example.com | 受講生 2号(4月期) |
| student | student3@example.com | 受講生 3号(4月期) |
| student | student4@example.com | 受講生 4号(10月期) |
| student | student5@example.com | 受講生 5号(10月期) |

## テスト実行

```bash
# Feature / Unit
php vendor/bin/phpunit --no-coverage

# Playwright E2E(事前に php artisan serve を別ターミナルで起動)
PLAYWRIGHT_BASE_URL=http://localhost:8000 npx playwright test
```

## 主要コマンド

| 目的 | コマンド |
| --- | --- |
| 開発サーバ起動(ローカル) | `php artisan serve --port=8000` |
| Vite 開発サーバ | `npm run dev` |
| アセットビルド | `npm run build` |
| DB 初期化 + シード | `php artisan migrate:fresh --seed` |
| ルート一覧 | `php artisan route:list` |
| コード整形 | `php vendor/bin/pint` |

## ロールと権限

- **admin**: 組織全体の受講生・日報・テスト・要注意者・CSV出力を管理
- **instructor**: 担当コホートの受講生・日報閲覧、テスト作成、コメント投稿
- **student**: 自身の日報提出、テスト受験、結果確認

詳細は [docs/permissions.md](docs/permissions.md) を参照。

## ドキュメント

- [docs/database-design.md](docs/database-design.md) — テーブル設計
- [docs/routes.md](docs/routes.md) — ルート設計
- [docs/screens.md](docs/screens.md) — 画面一覧
- [docs/permissions.md](docs/permissions.md) — 権限設計
- [CLAUDE.md](CLAUDE.md) — 開発原則(Claude Code 用指示)

## 要注意者検知の定期実行

実施中のコホートに対する要注意者検知は `risk:detect` コマンドで実行する。

```bash
php artisan risk:detect
```

`routes/console.php` で毎日 AM 6:00 に自動実行するようスケジュール登録済み。本番環境では `php artisan schedule:work` もしくは cron で `schedule:run` を 1 分ごとに起動する。

## 対象外機能(MVP 初版)

- 記述式問題、Slack通知、AI要約、PDF帳票、請求、複雑な契約・権限分離

## Git 運用

- `main` に直接コミットしない。常に `feature/*` や `fix/*` ブランチを切って PR する。
- コミットメッセージの prefix: `feat:` / `fix:` / `refactor:` / `test:` / `chore:` / `docs:`
