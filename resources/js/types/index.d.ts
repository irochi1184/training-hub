// バックエンドから渡されるデータの型定義

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'instructor' | 'student';
  organization_id: number;
}

export interface Organization {
  id: number;
  name: string;
}

export interface Course {
  id: number;
  name: string;
  description: string | null;
}

export interface Cohort {
  id: number;
  course_id: number;
  instructor_id: number;
  name: string;
  starts_on: string;
  ends_on: string;
  course?: Course;
  instructor?: User;
}

export interface Enrollment {
  id: number;
  cohort_id: number;
  user_id: number;
  enrolled_at: string;
  cohort?: Cohort;
  user?: User;
}

export interface DailyReport {
  id: number;
  user_id: number;
  cohort_id: number;
  reported_on: string;
  understanding_level: number; // 1〜5
  content: string;
  impression: string | null;
  created_at: string;
  user?: User;
  cohort?: Cohort;
  comments?: DailyReportComment[];
}

export interface DailyReportComment {
  id: number;
  daily_report_id: number;
  user_id: number;
  body: string;
  created_at: string;
  user?: User;
}

export interface Test {
  id: number;
  cohort_id: number;
  created_by: number;
  title: string;
  description: string | null;
  time_limit_minutes: number | null;
  opens_at: string | null;
  closes_at: string | null;
  cohort?: Cohort;
  questions?: Question[];
  questions_count?: number;
  submissions_count?: number;
}

export interface Question {
  id: number;
  test_id: number;
  body: string;
  position: number;
  score: number;
  choices?: Choice[];
}

export interface Choice {
  id: number;
  question_id: number;
  body: string;
  is_correct?: boolean; // 受験画面では送らない
  position: number;
}

export interface Submission {
  id: number;
  test_id: number;
  user_id: number;
  started_at: string;
  submitted_at: string | null;
  score: number | null;
  test?: Test;
  user?: User;
  answers?: Answer[];
}

export interface Answer {
  id: number;
  submission_id: number;
  question_id: number;
  choice_id: number | null;
  is_correct: boolean | null;
  question?: Question;
  choice?: Choice;
}

export interface RiskAlert {
  id: number;
  user_id: number;
  cohort_id: number;
  reason: 'low_understanding' | 'report_missing' | 'low_score';
  detail: string | null;
  resolved_at: string | null;
  created_at: string;
  user?: User;
  cohort?: Cohort;
}

export interface UnderstandingTrendItem {
  date: string;
  level: number; // 1〜5
}

export interface TestSummary {
  count: number;
  average: number | null;
  max: number | null;
  min: number | null;
}

export interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: { url: string | null; label: string; active: boolean }[];
}

// Inertia shared data
export interface PageProps {
  auth: {
    user: User;
  };
  flash?: {
    success?: string;
    error?: string;
  };
}
