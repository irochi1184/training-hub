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

export interface Curriculum {
  id: number;
  organization_id: number;
  name: string;
  starts_on: string;
  ends_on: string;
  main_instructors?: User[];
  sub_instructors?: User[];
}

export interface Enrollment {
  id: number;
  curriculum_id: number;
  user_id: number;
  enrolled_at: string;
  curriculum?: Curriculum;
  user?: User;
}

export interface DailyReport {
  id: number;
  user_id: number;
  curriculum_id: number;
  reported_on: string;
  understanding_level: number; // 1〜5
  content: string;
  impression: string | null;
  created_at: string;
  user?: User;
  curriculum?: Curriculum;
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
  curriculum_id: number;
  created_by: number;
  title: string;
  description: string | null;
  time_limit_minutes: number | null;
  opens_at: string | null;
  closes_at: string | null;
  max_attempts: number | null;
  curriculum?: Curriculum;
  creator?: User;
  questions?: Question[];
  questions_count?: number;
  submissions_count?: number;
  // 学生向け: 再受験情報
  my_attempts?: number;
  my_best_score?: number | null;
  remaining_attempts?: number | null;
}

export interface Question {
  id: number;
  test_id: number;
  body: string;
  question_type: 'single' | 'multiple';
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
  attempt: number;
  started_at: string;
  submitted_at: string | null;
  score: number | null;
  test?: Test;
  user?: User;
  answers?: Answer[];
}

export interface AttemptSummary {
  id: number;
  attempt: number;
  score: number | null;
  submitted_at: string | null;
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
  curriculum_id: number;
  reason: 'low_understanding' | 'report_missing' | 'low_score';
  detail: string | null;
  resolved_at: string | null;
  created_at: string;
  user?: User;
  curriculum?: Curriculum;
}

export interface UnderstandingTrendItem {
  date: string;
  level: number | null; // 1〜5、未提出日は null
}

export interface UnderstandingDistribution {
  curriculum_name: string;
  levels: [number, number, number, number, number]; // レベル1〜5の件数
}

export interface ReportRateItem {
  date: string;
  rate: number;
}

export interface CurriculumScoreItem {
  curriculum_name: string;
  avg_score: number | null;
}

export interface DashboardRiskAlert {
  id: number;
  reason: 'low_understanding' | 'report_missing' | 'low_score';
  detail: string | null;
  created_at: string | null;
  user_name: string | null;
  curriculum_name: string | null;
}

export interface CurriculumSummary {
  id: number;
  name: string;
  enrollment_count: number;
  avg_understanding: number | null;
  avg_score: number | null;
  unresolved_alert_count: number;
}

export interface TestSummary {
  count: number;
  average: number | null;
  max: number | null;
  min: number | null;
}

export interface ChoiceStat {
  choice_id: number;
  body: string;
  is_correct: boolean;
  count: number;
  rate: number;
}

export interface QuestionAnalytics {
  question_id: number;
  position: number;
  body: string;
  score: number;
  total_answers: number;
  correct_count: number;
  correct_rate: number;
  choice_stats: ChoiceStat[];
}

export interface AnalyticsSummary {
  total_submissions: number;
  avg_score: number | null;
  max_score: number | null;
  min_score: number | null;
  total_points: number;
}

export interface StudentSkill {
  id?: number;
  skill_name: string;
  level: 1 | 2 | 3;
}

export interface StudentProfile {
  id: number;
  user_id: number;
  bio: string | null;
  learning_goal: string | null;
  skills?: StudentSkill[];
}

export interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: { url: string | null; label: string; active: boolean }[];
}

export interface Announcement {
  id: number;
  organization_id: number;
  created_by: number;
  title: string;
  body: string;
  priority: 'normal' | 'important';
  target_type: 'all' | 'curriculum' | 'user';
  target_id: number | null;
  published_at: string | null;
  created_at: string;
  creator?: { id: number; name: string };
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
  unread_announcement_count?: number;
  risk_alert_count?: number;
}
