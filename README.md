# Training Hub

企業研修・スクール向けの受講管理アプリ。講師や運営担当が受講生の日報・理解度・テスト結果を一元管理し、状況を素早く把握できるようにする。

## 主な機能

- 受講生管理（一覧・詳細・理解度推移チャート）
- 日報提出と講師コメント
- 小テスト作成・受験・自動採点（複数選択・CSV取込・再受験対応）
- 要注意者検知（3日未提出 / 理解度平均2.0以下 / 得点率50%以下）
- CSV出力（日報・テスト結果、BOM付きUTF-8）
- ロール別ダッシュボード（admin / instructor / student）
- カリキュラム管理（複数講師対応）
- お知らせ（全体/カリキュラム宛、既読管理、一括既読）
- Slack通知（日報提出・コメント・要注意者検知・テスト完了・お知らせ投稿）
- AI要約（Ollama によるローカルLLM、週次日報サマリー・クラスレポート・要注意者説明）
- ユーザー管理（admin CRUD・パスワードリセット）

## 技術スタック

| レイヤー | 技術 |
|---|---|
| Backend | Laravel 13 / PHP 8.4 |
| Frontend | Inertia.js + Vue 3 + TypeScript + Tailwind CSS v4 |
| DB | MySQL 8（Docker） / SQLite（ローカル） |
| Queue | Redis（Docker） |
| AI | Ollama（ローカルLLM、gemma2 等） |
| PHP Test | PHPUnit 12（205テスト / 1033アサーション） |
| E2E Test | Playwright（17スペック） |
| Dev | Docker（Laravel Sail） |

## ディレクトリ構成

```
app/
├── Actions/          # 単位業務処理（DetectRiskAction, ScoreSubmissionAction 等）
├── Console/Commands/ # Artisanコマンド（risk:detect, summaries:generate-weekly）
├── Enums/            # RiskAlertReason, UserRole, NotificationEventType 等
├── Http/
│   ├── Controllers/  # リソースコントローラ
│   ├── Middleware/    # ロール制御ミドルウェア
│   └── Requests/     # FormRequest バリデーション
├── Jobs/             # Queue ジョブ（SendSlackNotificationJob）
├── Models/           # Eloquent モデル（17モデル）
├── Notifications/    # Slack通知クラス（5種）
├── Policies/         # 認可ポリシー
└── Services/         # 再利用処理（AiSummaryService, SlackNotificationService）

resources/js/
├── Components/       # 共通UIコンポーネント
├── Layouts/          # AppLayout
├── Pages/            # Inertia ページ（12ディレクトリ）
└── types/            # TypeScript 型定義

tests/
├── Feature/          # PHPUnit Feature テスト（25ファイル）
└── Unit/             # PHPUnit Unit テスト（3ファイル）

playwright/tests/     # Playwright E2E テスト（17ファイル）
```

## セットアップ

### 前提条件

- Docker Desktop
- Node.js 20+
- Composer 2+（初回インストール時）
- Ollama（AI要約機能を使う場合）

### Docker（推奨）

```bash
git clone https://github.com/irochi1184/training-hub.git
cd training-hub

cp .env.example .env
# .env を編集: DB_CONNECTION=mysql, DB_HOST=mysql, DB_DATABASE=training_hub,
#              DB_USERNAME=sail, DB_PASSWORD=password

composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

ブラウザで http://localhost を開く。

### ローカル実行（SQLite）

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm run dev &
php artisan serve --port=8000
```

ブラウザで http://localhost:8000 を開く。

## Seed アカウント

`migrate:fresh --seed` 後、以下のアカウントが利用可能。パスワードは全て `password`。

| ロール | メールアドレス | 備考 |
|---|---|---|
| admin | admin@example.com | 管理者 太郎 |
| instructor | instructor@example.com | 講師 花子（IT研修 メイン担当） |
| instructor | instructor2@example.com | 講師 次郎（ロジック研修 メイン担当） |
| student | student@example.com | 受講生 1号（IT研修） |
| student | student2〜5@example.com | 受講生 2〜5号 |

## テスト実行

```bash
# PHPテスト（全205件）
./vendor/bin/sail test

# ローカル実行
php vendor/bin/phpunit --no-coverage

# Playwright E2E テスト
npx playwright test

# Playwright UI モード
npx playwright test --ui
```

## AI要約の設定（Ollama）

```bash
# インストール
brew install ollama

# モデルダウンロード
ollama pull gemma2

# 起動
ollama serve
```

`.env` に追加（Docker環境の場合）:

```
OLLAMA_HOST=http://host.docker.internal:11434
OLLAMA_MODEL=gemma2
```

週次要約は毎週月曜06:00に自動生成。手動実行:

```bash
./vendor/bin/sail artisan summaries:generate-weekly
# 特定週を指定
./vendor/bin/sail artisan summaries:generate-weekly --week=2024-01-01
```

## Slack通知の設定

1. Slack で Incoming Webhook を作成
2. 管理者でログイン → サイドバー「通知設定」
3. Webhook URL を入力 → テスト送信で確認
4. 各イベント（日報提出・コメント・要注意者・テスト完了・お知らせ）のON/OFFを設定

## 主要コマンド

| 目的 | コマンド |
|---|---|
| 開発サーバ起動 | `./vendor/bin/sail up -d` |
| Vite 開発サーバ | `./vendor/bin/sail npm run dev` |
| アセットビルド | `./vendor/bin/sail npm run build` |
| DB 初期化 + シード | `./vendor/bin/sail artisan migrate:fresh --seed` |
| 要注意者検知 | `./vendor/bin/sail artisan risk:detect` |
| AI要約生成 | `./vendor/bin/sail artisan summaries:generate-weekly` |
| ルート一覧 | `./vendor/bin/sail artisan route:list` |
| コード整形 | `./vendor/bin/sail php vendor/bin/pint` |

## ロールと権限

- **admin**: 組織全体の管理（受講生・日報・テスト・要注意者・CSV・カリキュラム・ユーザー・通知設定・AI要約）
- **instructor**: 担当カリキュラムの管理（日報閲覧・コメント・テスト作成・アラート解消・AI要約閲覧）
- **student**: 自身の操作（日報提出・テスト受験・結果確認・プロフィール編集）

## 要注意者検知の定期実行

`risk:detect` コマンドで実施中のカリキュラムに対する要注意者検知を実行する。

```bash
./vendor/bin/sail artisan risk:detect
```

`routes/console.php` で毎日 AM 6:00 に自動実行するようスケジュール登録済み。
本番環境では cron で `schedule:run` を1分ごとに起動する。

## 本番デプロイ

[deploy/README.md](deploy/README.md) を参照。

## Git 運用

- `main` に直接コミットしない。常に `feature/*` / `fix/*` / `docs/*` ブランチを切って PR する
- コミットメッセージ prefix: `feat:` / `fix:` / `refactor:` / `test:` / `chore:` / `docs:`
