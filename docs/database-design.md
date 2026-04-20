# DB設計

## テーブル一覧

| テーブル名 | 概要 |
|---|---|
| organizations | 組織（企業・スクール） |
| users | ユーザー（admin / instructor / student） |
| courses | コース |
| cohorts | 期・クラス |
| enrollments | 受講登録（users ↔ cohorts の中間） |
| daily_reports | 日報 |
| daily_report_comments | 講師コメント |
| tests | 小テスト |
| questions | 問題 |
| choices | 選択肢 |
| submissions | テスト受験記録 |
| answers | 回答 |
| risk_alerts | 要注意者アラート |

---

## organizations

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | 組織名 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

---

## users

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| organization_id | BIGINT UNSIGNED | FK, NOT NULL | organizations.id |
| name | VARCHAR(255) | NOT NULL | 氏名 |
| email | VARCHAR(255) | UNIQUE, NOT NULL | |
| password | VARCHAR(255) | NOT NULL | ハッシュ済み |
| role | ENUM('admin','instructor','student') | NOT NULL | |
| deleted_at | TIMESTAMP | NULL | soft delete |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `organization_id`
- `email` (UNIQUE)
- `organization_id, role`

**リレーション:**
- `organization_id` → `organizations.id`

---

## courses

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| organization_id | BIGINT UNSIGNED | FK, NOT NULL | organizations.id |
| name | VARCHAR(255) | NOT NULL | コース名 |
| description | TEXT | NULL | |
| deleted_at | TIMESTAMP | NULL | soft delete |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `organization_id`

**リレーション:**
- `organization_id` → `organizations.id`

---

## cohorts

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| course_id | BIGINT UNSIGNED | FK, NOT NULL | courses.id |
| instructor_id | BIGINT UNSIGNED | FK, NOT NULL | users.id（講師） |
| name | VARCHAR(255) | NOT NULL | 例: 2024年4月期 |
| starts_on | DATE | NOT NULL | 開始日 |
| ends_on | DATE | NOT NULL | 終了日 |
| deleted_at | TIMESTAMP | NULL | soft delete |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `course_id`
- `instructor_id`

**リレーション:**
- `course_id` → `courses.id`
- `instructor_id` → `users.id`

---

## enrollments

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| cohort_id | BIGINT UNSIGNED | FK, NOT NULL | cohorts.id |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | users.id（student） |
| enrolled_at | DATE | NOT NULL | 登録日 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `cohort_id, user_id` (UNIQUE)
- `user_id`

**リレーション:**
- `cohort_id` → `cohorts.id`
- `user_id` → `users.id`

---

## daily_reports

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | users.id（student） |
| cohort_id | BIGINT UNSIGNED | FK, NOT NULL | cohorts.id |
| reported_on | DATE | NOT NULL | 対象日 |
| understanding_level | TINYINT UNSIGNED | NOT NULL | 1〜5（理解度） |
| content | TEXT | NOT NULL | 学習内容 |
| impression | TEXT | NULL | 感想・気づき |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `user_id, cohort_id, reported_on` (UNIQUE)
- `cohort_id, reported_on`

**リレーション:**
- `user_id` → `users.id`
- `cohort_id` → `cohorts.id`

---

## daily_report_comments

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| daily_report_id | BIGINT UNSIGNED | FK, NOT NULL | daily_reports.id |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | users.id（講師） |
| body | TEXT | NOT NULL | コメント本文 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `daily_report_id`

**リレーション:**
- `daily_report_id` → `daily_reports.id`
- `user_id` → `users.id`

---

## tests

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| cohort_id | BIGINT UNSIGNED | FK, NOT NULL | cohorts.id |
| created_by | BIGINT UNSIGNED | FK, NOT NULL | users.id（作成者） |
| title | VARCHAR(255) | NOT NULL | |
| description | TEXT | NULL | |
| time_limit_minutes | SMALLINT UNSIGNED | NULL | NULL=無制限 |
| opens_at | DATETIME | NULL | 公開開始日時 |
| closes_at | DATETIME | NULL | 公開終了日時 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `cohort_id`
- `cohort_id, opens_at, closes_at`

**リレーション:**
- `cohort_id` → `cohorts.id`
- `created_by` → `users.id`

---

## questions

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| test_id | BIGINT UNSIGNED | FK, NOT NULL | tests.id |
| body | TEXT | NOT NULL | 問題文 |
| position | SMALLINT UNSIGNED | NOT NULL | 表示順 |
| score | SMALLINT UNSIGNED | NOT NULL, DEFAULT 1 | 配点 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `test_id`
- `test_id, position`

**リレーション:**
- `test_id` → `tests.id`

---

## choices

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| question_id | BIGINT UNSIGNED | FK, NOT NULL | questions.id |
| body | TEXT | NOT NULL | 選択肢文 |
| is_correct | BOOLEAN | NOT NULL, DEFAULT FALSE | 正解フラグ |
| position | SMALLINT UNSIGNED | NOT NULL | 表示順 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `question_id`

**リレーション:**
- `question_id` → `questions.id`

---

## submissions

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| test_id | BIGINT UNSIGNED | FK, NOT NULL | tests.id |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | users.id（student） |
| started_at | DATETIME | NOT NULL | 受験開始日時 |
| submitted_at | DATETIME | NULL | 提出日時。NULL=未提出 |
| score | SMALLINT UNSIGNED | NULL | 合計得点。自動採点後に確定 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `test_id, user_id` (UNIQUE)
- `user_id`

**リレーション:**
- `test_id` → `tests.id`
- `user_id` → `users.id`

---

## answers

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| submission_id | BIGINT UNSIGNED | FK, NOT NULL | submissions.id |
| question_id | BIGINT UNSIGNED | FK, NOT NULL | questions.id |
| choice_id | BIGINT UNSIGNED | FK, NULL | choices.id。未回答はNULL |
| is_correct | BOOLEAN | NULL | 採点結果。採点前はNULL |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `submission_id`
- `submission_id, question_id` (UNIQUE)

**リレーション:**
- `submission_id` → `submissions.id`
- `question_id` → `questions.id`
- `choice_id` → `choices.id`

---

## risk_alerts

| カラム名 | 型 | 制約 | 備考 |
|---|---|---|---|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK, NOT NULL | users.id（student） |
| cohort_id | BIGINT UNSIGNED | FK, NOT NULL | cohorts.id |
| reason | ENUM('low_understanding','report_missing','low_score') | NOT NULL | 検知理由 |
| detail | TEXT | NULL | 補足情報 |
| resolved_at | DATETIME | NULL | 解消日時。NULL=未解消 |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**インデックス:**
- `user_id, cohort_id`
- `cohort_id, resolved_at`

**リレーション:**
- `user_id` → `users.id`
- `cohort_id` → `cohorts.id`

---

## リレーション全体図（概略）

```
organizations
  └── users (organization_id)
  └── courses (organization_id)
        └── cohorts (course_id)
              ├── enrollments (cohort_id) ← users
              ├── daily_reports (cohort_id) ← users
              │     └── daily_report_comments ← users
              ├── tests (cohort_id)
              │     ├── questions
              │     │     └── choices
              │     └── submissions ← users
              │           └── answers ← questions, choices
              └── risk_alerts ← users
```

---

## 設計上の判断メモ

- `students` テーブルは作らない。`users.role = 'student'` で判別する
- soft delete は `users`, `courses`, `cohorts` のみに限定。受験・採点データは物理削除しない
- `submissions` は 1ユーザー × 1テスト で UNIQUE 制約。再受験は初版では不要
- `risk_alerts.reason` は Enum で管理し、マジックストリングを排除する
- `understanding_level` は 1〜5 の整数で持ち、アプリ層でラベルを付ける
- `tests.opens_at / closes_at` は NULL で無期限を表現する
