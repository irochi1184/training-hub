<?php

namespace App\Services;

use App\Enums\SummaryType;
use App\Models\AiSummary;
use App\Models\Curriculum;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    private string $apiKey;

    private string $model = 'claude-sonnet-4-20250514';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
    }

    /**
     * 受講生の週次日報サマリーを生成して保存する
     */
    public function generateWeeklyStudentSummary(User $student, Carbon $weekStart): ?AiSummary
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $reports = $student->dailyReports()
            ->whereBetween('reported_on', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('reported_on')
            ->get(['reported_on', 'understanding_level', 'content', 'impression']);

        if ($reports->isEmpty()) {
            return null;
        }

        $reportText = $reports->map(function ($r) {
            $date = $r->reported_on->format('Y-m-d');
            $level = $r->understanding_level;
            $content = $r->content ?? '（記入なし）';
            $impression = $r->impression ? "感想: {$r->impression}" : '';
            return "[{$date}] 理解度: {$level}/5\n学習内容: {$content}\n{$impression}";
        })->join("\n\n");

        $systemPrompt = 'あなたは研修プログラムの学習支援アシスタントです。受講生の日報を分析し、簡潔で的確な要約を生成します。';
        $userMessage = "以下は受講生の1週間の日報です。学習状況を200字以内で要約し、良い点と改善点を挙げてください。\n\n{$reportText}";

        $content = $this->callClaude($systemPrompt, $userMessage);
        if ($content === null) {
            return null;
        }

        return AiSummary::updateOrCreate(
            [
                'organization_id' => $student->organization_id,
                'summarizable_type' => User::class,
                'summarizable_id' => $student->id,
                'summary_type' => SummaryType::WeeklyStudent->value,
                'week_start' => $weekStart->toDateString(),
            ],
            [
                'content' => $content,
                'week_end' => $weekEnd->toDateString(),
            ]
        );
    }

    /**
     * クラス全体の週次レポートを生成して保存する
     */
    public function generateWeeklyClassSummary(Curriculum $curriculum, Carbon $weekStart): ?AiSummary
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $reports = $curriculum->dailyReports()
            ->with('user:id,name')
            ->whereBetween('reported_on', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get(['id', 'user_id', 'reported_on', 'understanding_level', 'content']);

        if ($reports->isEmpty()) {
            return null;
        }

        // 統計情報を集計
        $totalStudents = $curriculum->enrollments()->count();
        $submittedStudents = $reports->pluck('user_id')->unique()->count();
        $submissionRate = $totalStudents > 0
            ? round(($submittedStudents / $totalStudents) * 100)
            : 0;
        $avgUnderstanding = round($reports->avg('understanding_level'), 1);

        $reportSummary = $reports->map(function ($r) {
            return "・{$r->user->name}: 理解度 {$r->understanding_level}/5 - {$r->content}";
        })->join("\n");

        $stats = "提出率: {$submissionRate}% ({$submittedStudents}/{$totalStudents}人)\n理解度平均: {$avgUnderstanding}/5";

        $systemPrompt = 'あなたは研修プログラムの学習支援アシスタントです。クラス全体の学習データを分析し、講師に向けた簡潔なレポートを生成します。';
        $userMessage = "以下はクラス全体の1週間の学習データです。300字以内で全体の傾向と注意点をまとめてください。\n\n{$stats}\n\n各受講生の状況:\n{$reportSummary}";

        $content = $this->callClaude($systemPrompt, $userMessage);
        if ($content === null) {
            return null;
        }

        return AiSummary::updateOrCreate(
            [
                'organization_id' => $curriculum->organization_id,
                'summarizable_type' => Curriculum::class,
                'summarizable_id' => $curriculum->id,
                'summary_type' => SummaryType::WeeklyClass->value,
                'week_start' => $weekStart->toDateString(),
            ],
            [
                'content' => $content,
                'week_end' => $weekEnd->toDateString(),
            ]
        );
    }

    /**
     * 要注意者の状況説明を生成して保存する
     */
    public function generateRiskExplanation(RiskAlert $alert): ?AiSummary
    {
        $alert->loadMissing(['user', 'curriculum']);

        $twoWeeksAgo = Carbon::today()->subWeeks(2);

        // 直近2週間の日報
        $reports = $alert->user->dailyReports()
            ->where('curriculum_id', $alert->curriculum_id)
            ->where('reported_on', '>=', $twoWeeksAgo->toDateString())
            ->orderBy('reported_on')
            ->get(['reported_on', 'understanding_level', 'content']);

        // 直近2週間のテスト結果
        $submissions = $alert->user->submissions()
            ->with('test:id,title')
            ->whereHas('test', fn ($q) => $q->where('curriculum_id', $alert->curriculum_id))
            ->where('submitted_at', '>=', $twoWeeksAgo)
            ->whereNotNull('submitted_at')
            ->get(['id', 'test_id', 'submitted_at', 'score']);

        $reportText = $reports->isNotEmpty()
            ? $reports->map(fn ($r) => "[{$r->reported_on->format('Y-m-d')}] 理解度: {$r->understanding_level}/5 - {$r->content}")->join("\n")
            : '（直近2週間の日報なし）';

        $testText = $submissions->isNotEmpty()
            ? $submissions->map(fn ($s) => "[{$s->submitted_at->format('Y-m-d')}] {$s->test->title}: {$s->score}点")->join("\n")
            : '（直近2週間の受験なし）';

        $alertDetail = $alert->detail ?? '詳細なし';
        $reason = $alert->reason->value;

        $systemPrompt = 'あなたは研修プログラムの学習支援アシスタントです。要注意者として検知された受講生の状況を分析し、講師に向けた的確な説明と推奨アクションを生成します。';
        $userMessage = "以下の受講生が要注意として検知されました。状況を150字以内で説明し、推奨アクションを提案してください。\n\n"
            . "受講生: {$alert->user->name}\n"
            . "カリキュラム: {$alert->curriculum->name}\n"
            . "検知理由: {$reason}\n"
            . "詳細: {$alertDetail}\n\n"
            . "直近2週間の日報:\n{$reportText}\n\n"
            . "直近2週間のテスト結果:\n{$testText}";

        $content = $this->callClaude($systemPrompt, $userMessage);
        if ($content === null) {
            return null;
        }

        return AiSummary::updateOrCreate(
            [
                'organization_id' => $alert->user->organization_id,
                'summarizable_type' => RiskAlert::class,
                'summarizable_id' => $alert->id,
                'summary_type' => SummaryType::RiskExplanation->value,
                'week_start' => null,
            ],
            [
                'content' => $content,
                'week_end' => null,
            ]
        );
    }

    /**
     * Claude API を呼び出して応答テキストを返す
     */
    private function callClaude(string $systemPrompt, string $userMessage): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Anthropic API キーが設定されていません。AI要約をスキップします。');
            return null;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        if ($response->failed()) {
            Log::warning('Claude API 呼び出しに失敗しました', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json('content.0.text');
    }
}
