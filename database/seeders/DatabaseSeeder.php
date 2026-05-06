<?php

namespace Database\Seeders;

use App\Enums\RiskAlertReason;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\Organization;
use App\Models\Question;
use App\Models\RiskAlert;
use App\Models\StudentProfile;
use App\Models\StudentSkill;
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

        $curriculum1Start = Carbon::today()->subMonths(3);
        $curriculum1End = Carbon::today()->addMonths(3);
        $curriculum2Start = Carbon::today()->subMonth();
        $curriculum2End = Carbon::today()->addMonths(5);

        $curriculum1 = Curriculum::create([
            'organization_id' => $org->id,
            'instructor_id' => $instructor1->id,
            'name' => 'IT研修',
            'starts_on' => $curriculum1Start->toDateString(),
            'ends_on' => $curriculum1End->toDateString(),
        ]);

        $curriculum2 = Curriculum::create([
            'organization_id' => $org->id,
            'instructor_id' => $instructor2->id,
            'name' => 'ロジック研修【Java】',
            'starts_on' => $curriculum2Start->toDateString(),
            'ends_on' => $curriculum2End->toDateString(),
        ]);

        foreach ($students->slice(0, 3) as $student) {
            Enrollment::create([
                'curriculum_id' => $curriculum1->id,
                'user_id' => $student->id,
                'enrolled_at' => $curriculum1Start->toDateString(),
            ]);
        }

        foreach ($students->slice(3, 2) as $student) {
            Enrollment::create([
                'curriculum_id' => $curriculum2->id,
                'user_id' => $student->id,
                'enrolled_at' => $curriculum2Start->toDateString(),
            ]);
        }

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
                    'curriculum_id' => $curriculum1->id,
                    'reported_on' => $date->format('Y-m-d'),
                    'understanding_level' => max(1, min(5, 3 + ($index % 3) - 1)),
                    'content' => "HTMLの基本構造について学びました。タグの意味と使い方を理解しました。",
                    'impression' => $dateOffset % 2 === 0 ? 'もう少し実践が欲しいと感じました' : null,
                ]);
            }
        }

        $test = Test::create([
            'curriculum_id' => $curriculum1->id,
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
                'question_type' => 'single',
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
                'question_type' => 'single',
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
                'question_type' => 'single',
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
                'question_type' => 'single',
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
                'question_type' => 'single',
                'score' => 2,
                'choices' => [
                    ['body' => '<table>', 'is_correct' => true],
                    ['body' => '<grid>', 'is_correct' => false],
                    ['body' => '<row>', 'is_correct' => false],
                    ['body' => '<list>', 'is_correct' => false],
                ],
            ],
            [
                'body' => 'HTMLのブロック要素をすべて選んでください（複数選択）',
                'question_type' => 'multiple',
                'score' => 2,
                'choices' => [
                    ['body' => '<div>', 'is_correct' => true],
                    ['body' => '<span>', 'is_correct' => false],
                    ['body' => '<p>', 'is_correct' => true],
                    ['body' => '<a>', 'is_correct' => false],
                ],
            ],
        ];

        $questions = collect();
        foreach ($questionsData as $position => $questionData) {
            $question = Question::create([
                'test_id' => $test->id,
                'body' => $questionData['body'],
                'question_type' => $questionData['question_type'] ?? 'single',
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

                if ($question->question_type === 'multiple') {
                    // 複数選択: 正解をすべて選択
                    $correctChoices = $choices->where('is_correct', true);
                    $isCorrect = true;
                    foreach ($correctChoices as $choice) {
                        Answer::create([
                            'submission_id' => $submission->id,
                            'question_id' => $question->id,
                            'choice_id' => $choice->id,
                            'is_correct' => true,
                        ]);
                    }
                    $totalScore += $question->score;
                } else {
                    // 単一選択
                    $correctChoice = $choices->firstWhere('is_correct', true);
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
            }

            $submission->update(['score' => $totalScore]);
        }

        // 再受験可能テスト（max_attempts=3）
        $retakeTest = Test::create([
            'curriculum_id' => $curriculum1->id,
            'created_by' => $instructor1->id,
            'title' => 'CSS基礎テスト（再受験可）',
            'description' => '再受験が3回まで可能なテストです',
            'time_limit_minutes' => null,
            'opens_at' => null,
            'closes_at' => null,
            'max_attempts' => 3,
        ]);

        $retakeQ = Question::create([
            'test_id' => $retakeTest->id,
            'body' => 'CSSでテキストの色を変えるプロパティはどれですか？',
            'question_type' => 'single',
            'position' => 1,
            'score' => 1,
        ]);
        Choice::create(['question_id' => $retakeQ->id, 'body' => 'color', 'is_correct' => true, 'position' => 1]);
        Choice::create(['question_id' => $retakeQ->id, 'body' => 'text-color', 'is_correct' => false, 'position' => 2]);
        Choice::create(['question_id' => $retakeQ->id, 'body' => 'font-color', 'is_correct' => false, 'position' => 3]);

        RiskAlert::create([
            'user_id' => $students->get(0)->id,
            'curriculum_id' => $curriculum1->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
            'detail' => '理解度平均: 2.0',
            'resolved_at' => null,
        ]);

        // プロフィールデータ
        $profile1 = StudentProfile::create([
            'user_id' => $students->get(0)->id,
            'bio' => 'プログラミング初心者です。IT業界への転職を目指しています。',
            'learning_goal' => '3ヶ月以内にWebアプリを一人で作れるようになる',
        ]);
        StudentSkill::insert([
            ['student_profile_id' => $profile1->id, 'skill_name' => 'HTML', 'level' => 2],
            ['student_profile_id' => $profile1->id, 'skill_name' => 'CSS', 'level' => 1],
            ['student_profile_id' => $profile1->id, 'skill_name' => 'JavaScript', 'level' => 1],
        ]);

        $profile2 = StudentProfile::create([
            'user_id' => $students->get(1)->id,
            'bio' => '大学で情報工学を学んでいます。実践的なスキルを身につけたいです。',
            'learning_goal' => 'Laravel でポートフォリオサイトを完成させる',
        ]);
        StudentSkill::insert([
            ['student_profile_id' => $profile2->id, 'skill_name' => 'PHP', 'level' => 2],
            ['student_profile_id' => $profile2->id, 'skill_name' => 'MySQL', 'level' => 2],
        ]);

        // お知らせ
        Announcement::create([
            'organization_id' => $org->id,
            'created_by' => $admin->id,
            'title' => '全体連絡: システムメンテナンスのお知らせ',
            'body' => '来週月曜日の深夜にシステムメンテナンスを実施します。ご不便をおかけしますが、ご了承ください。',
            'priority' => 'important',
            'target_type' => 'all',
            'target_id' => null,
            'published_at' => Carbon::now()->subDay(),
        ]);

        Announcement::create([
            'organization_id' => $org->id,
            'created_by' => $instructor1->id,
            'title' => 'IT研修: 次回の課題について',
            'body' => '次回までにHTMLの基本構造について予習しておいてください。',
            'priority' => 'normal',
            'target_type' => 'curriculum',
            'target_id' => $curriculum1->id,
            'published_at' => Carbon::now()->subHours(12),
        ]);
    }
}
