<?php

namespace Database\Seeders;

use App\Enums\RiskAlertReason;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\Organization;
use App\Models\Question;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create(['name' => 'サンプル株式会社']);

        $admin = User::create([
            'organization_id' => $org->id,
            'name' => '管理者 太郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin->value,
        ]);

        $instructor1 = User::create([
            'organization_id' => $org->id,
            'name' => '講師 花子',
            'email' => 'instructor@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Instructor->value,
        ]);

        $instructor2 = User::create([
            'organization_id' => $org->id,
            'name' => '講師 次郎',
            'email' => 'instructor2@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Instructor->value,
        ]);

        $students = collect();
        for ($i = 1; $i <= 5; $i++) {
            $email = $i === 1 ? 'student@example.com' : "student{$i}@example.com";
            $students->push(User::create([
                'organization_id' => $org->id,
                'name' => "受講生 {$i}号",
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => UserRole::Student->value,
            ]));
        }

        $course1 = Course::create([
            'organization_id' => $org->id,
            'name' => 'Webアプリ開発基礎',
            'description' => 'HTMLからLaravelまで学ぶ入門コース',
        ]);

        $course2 = Course::create([
            'organization_id' => $org->id,
            'name' => 'データ分析入門',
            'description' => 'PythonとPandasを使ったデータ分析コース',
        ]);

        $cohort1 = Cohort::create([
            'course_id' => $course1->id,
            'instructor_id' => $instructor1->id,
            'name' => '2024年4月期',
            'starts_on' => '2024-04-01',
            'ends_on' => '2024-09-30',
        ]);

        $cohort2 = Cohort::create([
            'course_id' => $course2->id,
            'instructor_id' => $instructor2->id,
            'name' => '2024年10月期',
            'starts_on' => '2024-10-01',
            'ends_on' => '2025-03-31',
        ]);

        // 受講生をコホートに登録（1〜3をcohort1、4〜5をcohort2）
        foreach ($students->slice(0, 3) as $student) {
            Enrollment::create([
                'cohort_id' => $cohort1->id,
                'user_id' => $student->id,
                'enrolled_at' => '2024-04-01',
            ]);
        }

        foreach ($students->slice(3, 2) as $student) {
            Enrollment::create([
                'cohort_id' => $cohort2->id,
                'user_id' => $student->id,
                'enrolled_at' => '2024-10-01',
            ]);
        }

        // 日報を数日分作成（cohort1の受講生）
        $reportDates = [
            Carbon::today()->subDays(4),
            Carbon::today()->subDays(3),
            Carbon::today()->subDays(2),
            Carbon::today()->subDays(1),
        ];

        foreach ($students->slice(0, 3) as $index => $student) {
            foreach ($reportDates as $dateOffset => $date) {
                DailyReport::create([
                    'user_id' => $student->id,
                    'cohort_id' => $cohort1->id,
                    'reported_on' => $date->format('Y-m-d'),
                    'understanding_level' => max(1, min(5, 3 + ($index % 3) - 1)),
                    'content' => "HTMLの基本構造について学びました。タグの意味と使い方を理解しました。",
                    'impression' => $dateOffset % 2 === 0 ? 'もう少し実践が欲しいと感じました' : null,
                ]);
            }
        }

        // テストを1つ作成（cohort1）
        $test = Test::create([
            'cohort_id' => $cohort1->id,
            'created_by' => $instructor1->id,
            'title' => 'HTML基礎テスト',
            'description' => 'HTMLの基本的な知識を確認するテストです',
            'time_limit_minutes' => 30,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        $questionsData = [
            [
                'body' => 'HTMLでリストを作成するタグはどれですか？',
                'score' => 1,
                'choices' => [
                    ['body' => '<list>', 'is_correct' => false],
                    ['body' => '<ul>', 'is_correct' => true],
                    ['body' => '<ol> と <ul> の両方', 'is_correct' => false],
                    ['body' => '<li>', 'is_correct' => false],
                ],
            ],
            [
                'body' => 'HTMLの文書型宣言を表すものはどれですか？',
                'score' => 1,
                'choices' => [
                    ['body' => '<!DOCTYPE html>', 'is_correct' => true],
                    ['body' => '<html>', 'is_correct' => false],
                    ['body' => '<head>', 'is_correct' => false],
                    ['body' => '<!--DOCTYPE-->', 'is_correct' => false],
                ],
            ],
            [
                'body' => 'リンクを作成するHTMLタグはどれですか？',
                'score' => 1,
                'choices' => [
                    ['body' => '<link>', 'is_correct' => false],
                    ['body' => '<href>', 'is_correct' => false],
                    ['body' => '<a>', 'is_correct' => true],
                    ['body' => '<url>', 'is_correct' => false],
                ],
            ],
            [
                'body' => '画像を表示するHTMLタグはどれですか？',
                'score' => 1,
                'choices' => [
                    ['body' => '<image>', 'is_correct' => false],
                    ['body' => '<img>', 'is_correct' => true],
                    ['body' => '<picture>', 'is_correct' => false],
                    ['body' => '<src>', 'is_correct' => false],
                ],
            ],
            [
                'body' => 'HTMLで表を作成するタグはどれですか？',
                'score' => 2,
                'choices' => [
                    ['body' => '<table>', 'is_correct' => true],
                    ['body' => '<grid>', 'is_correct' => false],
                    ['body' => '<row>', 'is_correct' => false],
                    ['body' => '<list>', 'is_correct' => false],
                ],
            ],
        ];

        $questions = collect();
        foreach ($questionsData as $position => $questionData) {
            $question = Question::create([
                'test_id' => $test->id,
                'body' => $questionData['body'],
                'position' => $position + 1,
                'score' => $questionData['score'],
            ]);

            $choiceModels = collect();
            foreach ($questionData['choices'] as $choicePosition => $choiceData) {
                $choiceModels->push(Choice::create([
                    'question_id' => $question->id,
                    'body' => $choiceData['body'],
                    'is_correct' => $choiceData['is_correct'],
                    'position' => $choicePosition + 1,
                ]));
            }

            $questions->push(['question' => $question, 'choices' => $choiceModels]);
        }

        // cohort1の受講生2名がテストを受験・採点済みにする
        foreach ($students->slice(0, 2) as $student) {
            $submission = Submission::create([
                'test_id' => $test->id,
                'user_id' => $student->id,
                'started_at' => Carbon::now()->subHours(2),
                'submitted_at' => Carbon::now()->subHour(),
                'score' => null,
            ]);

            $totalScore = 0;
            foreach ($questions as $questionData) {
                $question = $questionData['question'];
                $choices = $questionData['choices'];
                $correctChoice = $choices->firstWhere('is_correct', true);

                // 1問目はあえて不正解にする
                $selectedChoice = $question->position === 1
                    ? $choices->firstWhere('is_correct', false)
                    : $correctChoice;

                $isCorrect = $selectedChoice?->is_correct ?? false;

                Answer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $question->id,
                    'choice_id' => $selectedChoice?->id,
                    'is_correct' => $isCorrect,
                ]);

                if ($isCorrect) {
                    $totalScore += $question->score;
                }
            }

            $submission->update(['score' => $totalScore]);
        }

        // リスクアラートのサンプルを1件作成
        RiskAlert::create([
            'user_id' => $students->get(0)->id,
            'cohort_id' => $cohort1->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
            'detail' => '理解度平均: 2.0',
            'resolved_at' => null,
        ]);
    }
}
