# 権限設計

## 基本方針

- ロールによる粗い制御はミドルウェア (`EnsureRole`) で行う
- リソースごとの細かい制御は Laravel Policy で実装する
- Policy メソッドは `viewAny`, `view`, `create`, `update`, `delete` を基本とする
- 受講生は自分のデータのみ操作できる

---

## 凡例

| 記号 | 意味 |
|---|---|
| O | 許可 |
| - | 不可 |
| O* | 条件付き許可（備考参照） |

---

## users（UserPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O* | - | `viewAny` | instructorは担当curriculumのstudentのみ |
| 詳細表示 | O | O* | O* | `view` | instructorは担当studentのみ、studentは自分のみ |
| 作成 | O | - | - | `create` | |
| 更新 | O | - | O* | `update` | studentは自分のプロフィールのみ |
| 削除（soft） | O | - | - | `delete` | |

---

## curricula（CurriculumPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O | O | `viewAny` | |
| 詳細表示 | O | O | O | `view` | |
| 作成 | O | - | - | `create` | |
| 更新 | O | - | - | `update` | |
| 削除（soft） | O | - | - | `delete` | |

---

## curricula（CurriculumPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O | O | `viewAny` | |
| 詳細表示 | O | O | O | `view` | |
| 作成 | O | - | - | `create` | |
| 更新 | O | O* | - | `update` | instructorは自分が担当のcurriculumのみ |
| 削除（soft） | O | - | - | `delete` | |

---

## enrollments（EnrollmentPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O* | - | `viewAny` | instructorは担当curriculumのみ |
| 作成（登録） | O | - | - | `create` | |
| 削除（解除） | O | - | - | `delete` | |

---

## daily_reports（DailyReportPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O* | - | `viewAny` | instructorは担当curriculumのみ |
| 詳細表示 | O | O* | O* | `view` | instructorは担当curriculum、studentは自分のみ |
| 作成 | - | - | O | `create` | |
| 更新 | - | - | O* | `update` | studentは当日分のみ |
| 削除 | - | - | - | `delete` | 初版では削除不可 |

---

## daily_report_comments（DailyReportCommentPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 作成 | O | O* | - | `create` | instructorは担当curriculumの日報のみ |
| 削除 | O | O* | - | `delete` | instructorは自分のコメントのみ |

---

## tests（TestPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O | O* | `viewAny` | studentは受験可能なテストのみ |
| 詳細表示 | O | O | O* | `view` | studentは受験可能なテストのみ |
| 作成 | O | O* | - | `create` | instructorは担当curriculumのみ |
| 更新 | O | O* | - | `update` | instructorは担当curriculumのみ。受験者が存在する場合は不可 |
| 削除 | O | O* | - | `delete` | instructorは担当curriculumのみ。受験者が存在する場合は不可 |

---

## questions / choices

tests に準ずる。テストが更新不可の場合は questions / choices も更新不可。
個別のPolicyは設けず、`TestPolicy::update` の結果を流用する。

---

## submissions（SubmissionPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O* | - | `viewAny` | instructorは担当curriculumのみ |
| 詳細表示 | O | O* | O* | `view` | instructorは担当curriculum、studentは自分のみ |
| 作成（受験開始） | - | - | O* | `create` | studentは受験可能期間内かつ未受験のみ |

---

## risk_alerts（RiskAlertPolicy）

| 操作 | admin | instructor | student | Policyメソッド | 備考 |
|---|---|---|---|---|---|
| 一覧表示 | O | O* | - | `viewAny` | instructorは担当curriculumのみ |
| 解消マーク | O | O* | - | `resolve` | instructorは担当curriculumのみ |

※ `resolve` は標準の Policy メソッドではなく独自メソッドとして実装する。

---

## まとめ：ロール別できること

### admin
- 全組織データへのフルアクセス
- ユーザー作成・削除
- CSV出力

### instructor
- 担当 curriculum の受講生・日報・テストの閲覧・管理
- 日報へのコメント
- 担当 curriculum のテスト作成・編集（未受験に限る）
- 要注意者の確認・解消

### student
- 自分の日報提出（当日分の更新も可）
- 受験可能なテストの受験
- 自分のテスト結果の確認
- 自分のプロフィール編集

---

## Policy 実装時の注意

- `viewAny` でのフィルタは Policy で `false` を返すのではなく、Controller 側で scope を絞る
- Policy が `false` を返す場合は 403 レスポンスとなる
- `submissions.create` は「受験可能期間」と「未受験」の両方を確認する
- `tests.update` は `submissions` テーブルに受験記録が存在する場合は `false` を返す
