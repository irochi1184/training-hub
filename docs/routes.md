# ルート一覧

## ミドルウェア凡例

| 略称 | 意味 |
|---|---|
| `auth` | 認証済みユーザーのみ |
| `role:admin` | admin ロールのみ |
| `role:instructor` | instructor ロールのみ |
| `role:admin,instructor` | admin または instructor |
| `role:student` | student ロールのみ |

ロールチェックは `app/Http/Middleware/EnsureRole.php` で実装する。
細かいリソースへのアクセス制御は Policy で行う。

---

## 認証

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/login` | `AuthController@showLogin` | `guest` | `login` | ログイン画面 |
| POST | `/login` | `AuthController@login` | `guest` | `login.store` | ログイン処理 |
| POST | `/logout` | `AuthController@logout` | `auth` | `logout` | ログアウト |

---

## ダッシュボード

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/dashboard` | `DashboardController@index` | `auth` | `dashboard` | ダッシュボード（ロール別表示） |

---

## 受講生管理

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/students` | `StudentController@index` | `auth, role:admin,instructor` | `students.index` | 受講生一覧 |
| GET | `/students/{user}` | `StudentController@show` | `auth, role:admin,instructor` | `students.show` | 受講生詳細 |

---

## 日報

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/daily-reports` | `DailyReportController@index` | `auth, role:admin,instructor` | `daily-reports.index` | 日報一覧 |
| GET | `/daily-reports/create` | `DailyReportController@create` | `auth, role:student` | `daily-reports.create` | 日報入力画面 |
| POST | `/daily-reports` | `DailyReportController@store` | `auth, role:student` | `daily-reports.store` | 日報提出 |
| GET | `/daily-reports/{report}` | `DailyReportController@show` | `auth` | `daily-reports.show` | 日報詳細 |

---

## 講師コメント

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| POST | `/daily-reports/{report}/comments` | `DailyReportCommentController@store` | `auth, role:admin,instructor` | `daily-reports.comments.store` | コメント追加 |
| DELETE | `/daily-reports/{report}/comments/{comment}` | `DailyReportCommentController@destroy` | `auth, role:admin,instructor` | `daily-reports.comments.destroy` | コメント削除（自分のコメントのみ） |

---

## テスト

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/tests` | `TestController@index` | `auth` | `tests.index` | テスト一覧 |
| GET | `/tests/create` | `TestController@create` | `auth, role:admin,instructor` | `tests.create` | テスト作成画面 |
| POST | `/tests` | `TestController@store` | `auth, role:admin,instructor` | `tests.store` | テスト保存 |
| GET | `/tests/{test}/edit` | `TestController@edit` | `auth, role:admin,instructor` | `tests.edit` | テスト編集画面 |
| PUT | `/tests/{test}` | `TestController@update` | `auth, role:admin,instructor` | `tests.update` | テスト更新 |
| DELETE | `/tests/{test}` | `TestController@destroy` | `auth, role:admin,instructor` | `tests.destroy` | テスト削除 |

---

## テスト受験

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/tests/{test}/take` | `SubmissionController@create` | `auth, role:student` | `tests.take` | 受験画面 |
| POST | `/tests/{test}/submissions` | `SubmissionController@store` | `auth, role:student` | `tests.submissions.store` | 回答提出・自動採点 |
| GET | `/submissions/{submission}` | `SubmissionController@show` | `auth` | `submissions.show` | テスト結果 |

---

## 要注意者

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/risk-alerts` | `RiskAlertController@index` | `auth, role:admin,instructor` | `risk-alerts.index` | 要注意者一覧 |
| PATCH | `/risk-alerts/{alert}/resolve` | `RiskAlertController@resolve` | `auth, role:admin,instructor` | `risk-alerts.resolve` | アラート解消 |

---

## CSV出力

| メソッド | URI | Controller@method | ミドルウェア | 名前付きルート | 説明 |
|---|---|---|---|---|---|
| GET | `/exports` | `ExportController@index` | `auth, role:admin` | `exports.index` | CSV出力画面 |
| GET | `/exports/daily-reports` | `ExportController@dailyReports` | `auth, role:admin` | `exports.daily-reports` | 日報CSV出力 |
| GET | `/exports/test-results` | `ExportController@testResults` | `auth, role:admin` | `exports.test-results` | テスト結果CSV出力 |

---

## ルート設計上の判断メモ

- テスト受験の開始・提出は `submissions` リソースとして設計し、`SubmissionController` に寄せる
- コメントは日報にネストした形（`/daily-reports/{report}/comments`）で設計する
- CSV出力は `GET` でファイルダウンロードを返す。フィルタ条件はクエリパラメータで渡す
- テスト編集は未受験の場合のみ許可する制御を Policy で行う
- `role` ミドルウェアは粗い制御。個別リソースへのアクセス可否は Policy に委ねる
