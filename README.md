# Training Hub

企業研修・スクール向けの受講管理アプリ。講師や運営担当が受講生の日報・理解度・テスト結果を一元管理し、状況を素早く把握できるようにする。

## 主な機能

- 受講生管理（一覧・詳細・理解度推移チャート）
- 日報提出と講師コメント
- 小テスト作成・受験・自動採点
- 要注意者検知（3日未提出 / 理解度平均2.0以下 / 得点率50%以下）
- CSV出力（日報・テスト結果、BOM付きUTF-8）
- ロール別ダッシュボード（admin / instructor / student）
- カリキュラム管理（admin CRUD）

## 技術スタック

| レイヤー | 技術 |
|---|---|
| Backend | Laravel 13 / PHP 8.4 |
| Frontend | Inertia.js + Vue 3 + TypeScript + Tailwind CSS v4 |
| DB | MySQL 8（Docker） / SQLite（ローカル） |
| Queue | Redis（Docker） |
| PHP Test | PHPUnit 12（90テスト / 366アサーション） |
| E2E Test | Playwright 1.59（21テスト） |
| Dev | Docker（Laravel Sail） |

## ディレクトリ構成

```
app/
├── Actions/          # 単位業務処理（DetectRiskAction, ScoreSubmissionAction 等）
├── Enums/            # RiskAlertReason, Role 等
├── Http/
│   ├── Controllers/  # リソースコントローラ
│   ├── Middleware/    # ロール制御ミドルウェア
│   └── Requests/     # FormRequest バリデーション
├── Models/           # Eloquent モデル（12モデル）
├── Policies/         # 認可ポリシー
└── Services/         # 再利用処理

resources/js/
├── Components/       # 共通UIコンポーネント
├── Layouts/          # AppLayout
├── Pages/            # Inertia ページ（9ディレクトリ）
├── types/            # TypeScript 型定義
└── utils/            # formatDate, understandingLevel 等

tests/
├── Feature/          # PHPUnit Feature テスト（13ファイル）
└── Unit/             # PHPUnit Unit テスト

playwright/tests/     # Playwright E2E テスト（9ファイル）

docs/                 # 設計ドキュメント
```

## セットアップ

### 前提条件

- PHP 8.4 / Composer / Node.js 20+
- Docker（Sail利用時）

### パターン A: ローカル実行（SQLite）

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

### パターン B: Docker（Laravel Sail / MySQL）

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
|---|---|---|
| admin | admin@example.com | 管理者 太郎 |
| instructor | instructor@example.com | 講師 花子（IT研修 担当） |
| instructor | instructor2@example.com | 講師 次郎（ロジック研修 担当） |
| student | student@example.com | 受講生 1号（IT研修） |
| student | student2@example.com | 受講生 2号（IT研修） |
| student | student3@example.com | 受講生 3号（IT研修） |
| student | student4@example.com | 受講生 4号（ロジック研修） |
| student | student5@example.com | 受講生 5号（ロジック研修） |

## テスト実行

```bash
# Feature / Unit テスト（90テスト）
php vendor/bin/phpunit --no-coverage

# Docker 環境の場合
./vendor/bin/sail test

# Playwright E2E テスト（21テスト）
npx playwright test

# Playwright UI モード
npx playwright test --ui
```

Playwright は `playwright/global-setup.ts` で `migrate:fresh --seed --force` を実行し、
`playwright.config.ts` の `webServer` 設定で `127.0.0.1:8000` の開発サーバを自動起動する。
別ポートに向ける場合は `PLAYWRIGHT_BASE_URL` 環境変数を上書きする。

### テストカバレッジ

| カテゴリ | ファイル数 | テスト数 |
|---|---|---|
| Feature Test | 13 | 90 |
| Playwright E2E | 9 | 21 |
| **合計** | **22** | **111** |

## 主要コマンド

| 目的 | コマンド |
|---|---|
| 開発サーバ起動（ローカル） | `php artisan serve --port=8000` |
| Vite 開発サーバ | `npm run dev` |
| アセットビルド | `npm run build` |
| DB 初期化 + シード | `php artisan migrate:fresh --seed` |
| 要注意者検知 | `php artisan risk:detect` |
| ルート一覧 | `php artisan route:list` |
| コード整形 | `php vendor/bin/pint` |

## ロールと権限

- **admin**: 組織全体の受講生・日報・テスト・要注意者・CSV出力・カリキュラムを管理
- **instructor**: 担当カリキュラムの受講生・日報閲覧、テスト作成、コメント投稿、アラート解消
- **student**: 自身の日報提出、テスト受験、結果確認

詳細は [docs/permissions.md](docs/permissions.md) を参照。

## 要注意者検知の定期実行

`risk:detect` コマンドで実施中のカリキュラムに対する要注意者検知を実行する。

```bash
php artisan risk:detect
```

`routes/console.php` で毎日 AM 6:00 に自動実行するようスケジュール登録済み。
本番環境では `php artisan schedule:work` もしくは cron で `schedule:run` を1分ごとに起動する。

## ドキュメント

- [docs/database-design.md](docs/database-design.md) — テーブル設計
- [docs/routes.md](docs/routes.md) — ルート設計
- [docs/screens.md](docs/screens.md) — 画面一覧
- [docs/permissions.md](docs/permissions.md) — 権限設計
- [CLAUDE.md](CLAUDE.md) — 開発原則

## 対象外機能（MVP 初版）

記述式問題、Slack通知、AI要約、PDF帳票、請求、複雑な契約・権限分離

## Git 運用

- `main` に直接コミットしない。常に `feature/*` / `fix/*` / `docs/*` ブランチを切って PR する
- コミットメッセージ prefix: `feat:` / `fix:` / `refactor:` / `test:` / `chore:` / `docs:`
