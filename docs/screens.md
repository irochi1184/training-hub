# 画面一覧

## ロール凡例

| 略称 | 意味 |
|---|---|
| A | admin |
| I | instructor |
| S | student |

---

## 画面一覧表

| 画面名 | URL | アクセス可能ロール | 主な機能 | 主なコンポーネント |
|---|---|---|---|---|
| ログイン | `/login` | 全員（未認証） | メール・パスワードでログイン | AuthLayout, LoginForm |
| ダッシュボード | `/dashboard` | A / I / S | ロール別のサマリー表示（提出状況、テスト進捗、要注意者数） | DashboardLayout, StatCard, AlertBanner |
| 受講生一覧 | `/students` | A / I | 受講生の検索・絞り込み、一覧表示、要注意マーク | StudentTable, SearchFilter |
| 受講生詳細 | `/students/{user}` | A / I | 基本情報・日報一覧・テスト結果・要注意アラート一覧 | StudentProfile, ReportSummary, TestResultList, AlertList |
| 日報入力 | `/daily-reports/create` | S | 学習内容・理解度・感想の入力・提出 | DailyReportForm, UnderstandingSelector |
| 日報一覧 | `/daily-reports` | A / I | 日報の一覧表示・絞り込み（期・日付・受講生） | ReportTable, DateRangeFilter |
| 日報詳細 | `/daily-reports/{report}` | A / I / S | 日報の内容表示、講師コメントの確認・追加 | ReportDetail, CommentList, CommentForm |
| テスト一覧 | `/tests` | A / I / S | テスト一覧（studentは受験可能なテストのみ） | TestTable, StatusBadge |
| テスト作成 | `/tests/create` | A / I | タイトル・問題・選択肢の入力、公開期間設定 | TestForm, QuestionEditor, ChoiceEditor |
| テスト編集 | `/tests/{test}/edit` | A / I | 既存テストの修正（未受験のみ） | TestForm, QuestionEditor, ChoiceEditor |
| テスト受験 | `/tests/{test}/take` | S | 選択肢を選んで提出、制限時間カウント | ExamLayout, QuestionCard, ChoiceList, SubmitButton |
| テスト結果 | `/submissions/{submission}` | A / I / S | 得点・正誤・解答内容の確認（studentは自分のみ） | ResultSummary, AnswerReview |
| 要注意者一覧 | `/risk-alerts` | A / I | 要注意フラグのある受講生一覧、理由・解消状況 | AlertTable, ReasonBadge |
| CSV出力 | `/exports` | A | エクスポート種別の選択・ダウンロード（日報・テスト結果） | ExportForm, DownloadButton |

---

## ダッシュボードのロール別表示内容

| ロール | 表示内容 |
|---|---|
| admin | 全カリキュラムの要注意者数、直近の日報提出率、テスト受験完了率 |
| instructor | 担当カリキュラムの要注意者数、本日の日報提出状況、直近のテスト平均点 |
| student | 自分の直近日報、直近テスト結果、未提出日報の有無 |

---

## 画面遷移の主な流れ

```
ログイン
  └── ダッシュボード
        ├── (A/I) 受講生一覧 → 受講生詳細
        ├── (A/I) 日報一覧 → 日報詳細（コメント追加）
        ├── (A/I) テスト一覧 → テスト作成 / テスト結果
        ├── (A/I) 要注意者一覧 → 受講生詳細
        ├── (A)   CSV出力
        └── (S)   日報入力
                  テスト一覧 → テスト受験 → テスト結果
```

---

## レイアウト構成

| レイアウト名 | 用途 |
|---|---|
| AuthLayout | ログイン画面（サイドバーなし） |
| AppLayout | 認証済みの全画面共通（ナビゲーションバー + サイドバー） |
| ExamLayout | テスト受験専用（ナビゲーションを最小化、残り時間表示） |
