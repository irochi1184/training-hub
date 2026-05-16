<?php

namespace Tests\Feature;

use App\Enums\RiskAlertReason;
use App\Enums\SummaryType;
use App\Models\AiSummary;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Enrollment;
use App\Models\Organization;
use App\Models\RiskAlert;
use App\Models\User;
use App\Services\AiSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiSummaryTest extends TestCase
{
    use RefreshDatabase;

    // Claude API のモックレスポンス
    private function fakeClaudeResponse(string $text): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ], 200),
        ]);
    }

    private function fakeClaudeFailure(int $status = 500): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response(['error' => 'server error'], $status),
        ]);
    }

    // APIキーをテスト用に設定するヘルパー
    private function withApiKey(): void
    {
        config(['services.anthropic.api_key' => 'test-api-key']);
    }

    // =====================================================
    // generateWeeklyStudentSummary
    // =====================================================

    public function test_受講生の週次サマリーが生成されDBに保存される(): void
    {
        $this->withApiKey();
        $this->fakeClaudeResponse('良い点: 理解度が高い。改善点: 感想が少ない。');

        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        $weekStart = Carbon::parse('2024-01-01');
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'reported_on' => '2024-01-02',
            'understanding_level' => 4,
            'content' => 'Laravelの基礎を学んだ',
        ]);

        $service = $this->app->make(AiSummaryService::class);
        $summary = $service->generateWeeklyStudentSummary($student, $weekStart);

        $this->assertNotNull($summary);
        $this->assertInstanceOf(AiSummary::class, $summary);
        $this->assertEquals(SummaryType::WeeklyStudent, $summary->summary_type);
        $this->assertEquals($student->id, $summary->summarizable_id);
        $this->assertEquals(User::class, $summary->summarizable_type);
        $this->assertEquals('良い点: 理解度が高い。改善点: 感想が少ない。', $summary->content);

        $this->assertDatabaseHas('ai_summaries', [
            'summarizable_type' => User::class,
            'summarizable_id' => $student->id,
            'summary_type' => SummaryType::WeeklyStudent->value,
            'week_start' => '2024-01-01',
        ]);
    }

    public function test_対象週に日報がない場合はnullを返す(): void
    {
        $this->withApiKey();

        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);
        $weekStart = Carbon::parse('2024-01-01');

        $service = $this->app->make(AiSummaryService::class);
        $result = $service->generateWeeklyStudentSummary($student, $weekStart);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_同じ週のサマリーは上書き更新される(): void
    {
        $this->withApiKey();
        $this->fakeClaudeResponse('更新後のサマリー');

        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);
        $weekStart = Carbon::parse('2024-01-01');

        // 事前に既存サマリーを作成
        AiSummary::create([
            'organization_id' => $org->id,
            'summarizable_type' => User::class,
            'summarizable_id' => $student->id,
            'summary_type' => SummaryType::WeeklyStudent->value,
            'content' => '古いサマリー',
            'week_start' => '2024-01-01',
            'week_end' => '2024-01-07',
        ]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'reported_on' => '2024-01-02',
            'understanding_level' => 3,
        ]);

        $service = $this->app->make(AiSummaryService::class);
        $service->generateWeeklyStudentSummary($student, $weekStart);

        // DBに1件のみ存在する（upsert されている）
        $this->assertDatabaseCount('ai_summaries', 1);
        $this->assertDatabaseHas('ai_summaries', [
            'summarizable_id' => $student->id,
            'content' => '更新後のサマリー',
        ]);
    }

    // =====================================================
    // generateWeeklyClassSummary
    // =====================================================

    public function test_クラス週次サマリーが生成されDBに保存される(): void
    {
        $this->withApiKey();
        $this->fakeClaudeResponse('全体的に理解度が高い週でした。');

        $org = Organization::factory()->create();
        $curriculum = Curriculum::factory()->create(['organization_id' => $org->id]);
        $student1 = User::factory()->student()->create(['organization_id' => $org->id]);
        $student2 = User::factory()->student()->create(['organization_id' => $org->id]);

        Enrollment::factory()->create(['curriculum_id' => $curriculum->id, 'user_id' => $student1->id]);
        Enrollment::factory()->create(['curriculum_id' => $curriculum->id, 'user_id' => $student2->id]);

        $weekStart = Carbon::parse('2024-01-01');
        DailyReport::factory()->create([
            'user_id' => $student1->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => '2024-01-02',
            'understanding_level' => 4,
            'content' => 'Laravelのルーティングを学んだ',
        ]);
        DailyReport::factory()->create([
            'user_id' => $student2->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => '2024-01-03',
            'understanding_level' => 3,
            'content' => 'Eloquentの使い方を学んだ',
        ]);

        $service = $this->app->make(AiSummaryService::class);
        $summary = $service->generateWeeklyClassSummary($curriculum, $weekStart);

        $this->assertNotNull($summary);
        $this->assertEquals(SummaryType::WeeklyClass, $summary->summary_type);
        $this->assertEquals($curriculum->id, $summary->summarizable_id);
        $this->assertEquals(Curriculum::class, $summary->summarizable_type);

        $this->assertDatabaseHas('ai_summaries', [
            'summarizable_type' => Curriculum::class,
            'summarizable_id' => $curriculum->id,
            'summary_type' => SummaryType::WeeklyClass->value,
        ]);
    }

    public function test_クラスに日報がなければnullを返す(): void
    {
        $this->withApiKey();

        $org = Organization::factory()->create();
        $curriculum = Curriculum::factory()->create(['organization_id' => $org->id]);
        $weekStart = Carbon::parse('2024-01-01');

        $service = $this->app->make(AiSummaryService::class);
        $result = $service->generateWeeklyClassSummary($curriculum, $weekStart);

        $this->assertNull($result);
    }

    // =====================================================
    // generateRiskExplanation
    // =====================================================

    public function test_要注意者の状況説明が生成されDBに保存される(): void
    {
        $this->withApiKey();
        $this->fakeClaudeResponse('理解度が低下傾向。推奨アクション: 個別面談を実施してください。');

        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);
        $curriculum = Curriculum::factory()->create(['organization_id' => $org->id]);

        $alert = RiskAlert::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
            'detail' => '理解度平均: 1.5',
        ]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => Carbon::today()->subDays(3)->toDateString(),
            'understanding_level' => 1,
            'content' => '難しくて理解が追いつかない',
        ]);

        $service = $this->app->make(AiSummaryService::class);
        $summary = $service->generateRiskExplanation($alert);

        $this->assertNotNull($summary);
        $this->assertEquals(SummaryType::RiskExplanation, $summary->summary_type);
        $this->assertEquals($alert->id, $summary->summarizable_id);
        $this->assertEquals(RiskAlert::class, $summary->summarizable_type);
        $this->assertStringContainsString('個別面談', $summary->content);

        $this->assertDatabaseHas('ai_summaries', [
            'summarizable_type' => RiskAlert::class,
            'summarizable_id' => $alert->id,
            'summary_type' => SummaryType::RiskExplanation->value,
        ]);
    }

    // =====================================================
    // APIキー未設定時
    // =====================================================

    public function test_APIキー未設定の場合はnullを返しAPIを呼ばない(): void
    {
        // APIキーを空にする
        config(['services.anthropic.api_key' => '']);

        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'reported_on' => Carbon::parse('2024-01-02')->toDateString(),
            'understanding_level' => 3,
        ]);

        Http::fake();

        $service = $this->app->make(AiSummaryService::class);
        $result = $service->generateWeeklyStudentSummary($student, Carbon::parse('2024-01-01'));

        $this->assertNull($result);
        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_summaries', 0);
    }

    // =====================================================
    // API呼び出し失敗
    // =====================================================

    public function test_Claude_APIがエラーを返した場合はnullを返す(): void
    {
        $this->withApiKey();
        $this->fakeClaudeFailure(500);

        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'reported_on' => '2024-01-02',
            'understanding_level' => 3,
        ]);

        $service = $this->app->make(AiSummaryService::class);
        $result = $service->generateWeeklyStudentSummary($student, Carbon::parse('2024-01-01'));

        $this->assertNull($result);
        $this->assertDatabaseCount('ai_summaries', 0);
    }

    // =====================================================
    // Artisan コマンド
    // =====================================================

    public function test_コマンド実行で全受講生と全カリキュラムのサマリーを生成する(): void
    {
        $this->withApiKey();
        $this->fakeClaudeResponse('週次サマリーの内容です。');

        $org = Organization::factory()->create();
        $curriculum = Curriculum::factory()->create(['organization_id' => $org->id]);
        $student = User::factory()->student()->create(['organization_id' => $org->id]);
        Enrollment::factory()->create(['curriculum_id' => $curriculum->id, 'user_id' => $student->id]);

        $weekStart = '2024-01-01';
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => '2024-01-02',
            'understanding_level' => 4,
            'content' => 'テスト内容',
        ]);

        $this->artisan("summaries:generate-weekly --week={$weekStart}")
            ->assertExitCode(0);

        // 受講生サマリーとクラスサマリーの2件が生成されている
        $this->assertDatabaseHas('ai_summaries', [
            'summarizable_type' => User::class,
            'summarizable_id' => $student->id,
            'summary_type' => SummaryType::WeeklyStudent->value,
        ]);

        $this->assertDatabaseHas('ai_summaries', [
            'summarizable_type' => Curriculum::class,
            'summarizable_id' => $curriculum->id,
            'summary_type' => SummaryType::WeeklyClass->value,
        ]);
    }

    public function test_対象データがなくてもコマンドは正常終了する(): void
    {
        $this->withApiKey();

        $this->artisan('summaries:generate-weekly --week=2024-01-01')
            ->expectsOutputToContain('0 件')
            ->assertExitCode(0);
    }

    // =====================================================
    // Controller
    // =====================================================

    public function test_管理者はサマリー一覧を取得できる(): void
    {
        $org = Organization::factory()->create();
        $admin = User::factory()->admin()->create(['organization_id' => $org->id]);
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        AiSummary::create([
            'organization_id' => $org->id,
            'summarizable_type' => User::class,
            'summarizable_id' => $student->id,
            'summary_type' => SummaryType::WeeklyStudent->value,
            'content' => 'テストサマリー内容',
            'week_start' => '2024-01-01',
            'week_end' => '2024-01-07',
        ]);

        $response = $this->actingAs($admin)->get('/ai-summaries');
        $response->assertOk();
    }

    public function test_受講生はサマリー一覧にアクセスできない(): void
    {
        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($student)->get('/ai-summaries');
        $response->assertForbidden();
    }

    public function test_管理者は手動でサマリーを生成できる(): void
    {
        $this->withApiKey();
        $this->fakeClaudeResponse('手動生成サマリーです。');

        $org = Organization::factory()->create();
        $admin = User::factory()->admin()->create(['organization_id' => $org->id]);
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        DailyReport::factory()->create([
            'user_id' => $student->id,
            'reported_on' => '2024-01-02',
            'understanding_level' => 4,
            'content' => 'テスト内容',
        ]);

        $response = $this->actingAs($admin)->post('/ai-summaries/generate', [
            'summary_type' => SummaryType::WeeklyStudent->value,
            'target_id' => $student->id,
            'week_start' => '2024-01-01',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_受講生は手動生成エンドポイントにアクセスできない(): void
    {
        $org = Organization::factory()->create();
        $student = User::factory()->student()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($student)->post('/ai-summaries/generate', [
            'summary_type' => SummaryType::WeeklyStudent->value,
            'target_id' => $student->id,
            'week_start' => '2024-01-01',
        ]);

        $response->assertForbidden();
    }
}
