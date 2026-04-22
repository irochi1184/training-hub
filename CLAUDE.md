# プロジェクト概要

このアプリは、企業研修・スクール向けの受講管理アプリである。
初版MVPの目的は、講師や運営担当が受講生の状況を早く把握し、
日報、理解度、テスト結果を一元管理できるようにすること。

# 初版で実装する機能

- 受講生管理
- 日報提出
- 講師コメント
- 理解度の可視化
- 小テスト作成
- テスト受験
- 自動採点
- 要注意者検知
- CSV出力

# 初版で実装しない機能

- 記述式問題
- Slack通知
- AI要約
- PDF帳票
- 請求機能
- 複雑な契約管理
- 複雑な権限分離

# 技術スタック

- Backend: Laravel 13
- PHP: 8.4系
- Frontend: Inertia + Vue 3 + TypeScript + Tailwind CSS
- DB: MySQL 8
- Queue: Redis
- Browser Test: Playwright
- PHP Test: Pest または PHPUnit
- Dev: Docker

# 開発原則

- Laravel標準構成を大きく崩さない
- Controllerを太らせない
- バリデーションはFormRequestに寄せる
- 業務処理はActions / Servicesに寄せる
- 権限制御はPolicyで実装する
- 一覧取得ではN+1を避ける
- フロントはPagesとComponentsを分ける
- 初版では過剰設計しない
- コメントは必要最小限にする
- 命名で意味が伝わるようにする

# 想定ロール

- admin: 組織管理者。全体管理ができる
- instructor: 講師。担当クラスと担当受講生を管理できる
- student: 受講生。自分の日報提出とテスト受験ができる

# 想定テーブル

- organizations
- users
- curricula
- students
- enrollments
- daily_reports
- daily_report_comments
- tests
- questions
- choices
- submissions
- answers
- risk_alerts

# 主要画面

- ログイン
- ダッシュボード
- 受講生一覧
- 受講生詳細
- 日報入力
- 日報一覧
- テスト作成
- テスト受験
- テスト結果
- CSV出力

# 実装順

1. 設計
2. migration / model / policy / request
3. 受講生管理
4. 日報と講師コメント
5. テスト作成と受験
6. 自動採点
7. 要注意者検知
8. CSV出力
9. Feature Test
10. Playwright試験
11. 統合と整理

# Claude Codeへの指示

- まず設計を固めてから実装する
- 可能な作業は担当へ並列委任する
- 同じファイルへ同時に大きな変更を入れない
- 変更後は関連試験を追加または更新する
- 変更の最後に、未解決課題と次の一手を必ず短く整理する

# ディレクトリ方針

- app/Models にモデル
- app/Http/Controllers にコントローラ
- app/Http/Requests に入力検証
- app/Actions に単位業務処理
- app/Services に再利用処理
- app/Policies に権限制御
- resources/js/Pages に画面
- resources/js/Components に共通部品
- tests/Feature に機能試験
- tests/Unit に単体試験
- playwright/tests に通し試験

# コード品質

- 新規実装では型と戻り値を意識する
- null許容は必要性が明確な場合だけ使う
- マジックナンバーを避ける
- Enum化できるものはEnumを検討する
- 表示文言は乱雑に埋め込まない
- CSV出力は日本語利用者を意識する

# UI方針

- 派手さより読みやすさ
- テーブルは見やすく
- 入力欄は迷わせない
- 危険状態や未提出は目立たせる
- 操作導線は短くする

# Git運用ルール

- main に直接コミットしない
- 必ず feature ブランチを切る

命名規則:

feature/student-management
feature/daily-reports
feature/test-engine
fix/scoring-bug
refactor/risk-detection

- 作業前に git status を確認する
- 作業後に git diff を確認する
- 変更ごとに意味のある commit を作る
- 1 commit に unrelated な変更を混ぜない

commit message 形式:

feat: add daily report submission
fix: correct auto scoring logic
refactor: extract risk detection service
test: add playwright flow for test submission

- merge は勝手にしない
- rebase は勝手にしない
- force push はしない