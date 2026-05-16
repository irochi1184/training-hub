<?php

namespace Tests\Feature;

use App\Enums\NotificationEventType;
use App\Jobs\SendSlackNotificationJob;
use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Models\Enrollment;
use App\Models\NotificationSetting;
use App\Models\Organization;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SlackNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $admin;
    private User $instructor;
    private User $student;
    private Curriculum $curriculum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create([
            'slack_webhook_url' => 'https://hooks.slack.com/services/test/webhook',
        ]);

        $this->admin = User::factory()->admin()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->instructor = User::factory()->instructor()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->student = User::factory()->student()->create([
            'organization_id' => $this->org->id,
        ]);

        $this->curriculum = Curriculum::factory()->create([
            'organization_id' => $this->org->id,
        ]);

        Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'curriculum_id' => $this->curriculum->id,
        ]);
    }

    /** 通知設定が有効なとき日報提出でジョブがディスパッチされる */
    public function test_日報提出で通知ジョブがディスパッチされる(): void
    {
        Queue::fake();

        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::DailyReportSubmitted->value,
            'enabled' => true,
        ]);

        $this->actingAs($this->student)
            ->post('/daily-reports', [
                'curriculum_id' => $this->curriculum->id,
                'reported_on' => now()->format('Y-m-d'),
                'understanding_level' => 3,
                'content' => '今日の学習内容',
                'impression' => '良かった',
            ]);

        Queue::assertPushed(SendSlackNotificationJob::class);
    }

    /** Webhook URLが未設定ならジョブはディスパッチされない */
    public function test_WebhookURL未設定なら通知ジョブはディスパッチされない(): void
    {
        Queue::fake();

        $this->org->update(['slack_webhook_url' => null]);

        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::DailyReportSubmitted->value,
            'enabled' => true,
        ]);

        $this->actingAs($this->student)
            ->post('/daily-reports', [
                'curriculum_id' => $this->curriculum->id,
                'reported_on' => now()->format('Y-m-d'),
                'understanding_level' => 3,
                'content' => '今日の学習内容',
                'impression' => '良かった',
            ]);

        Queue::assertNotPushed(SendSlackNotificationJob::class);
    }

    /** 通知設定が無効ならジョブはディスパッチされない */
    public function test_通知設定無効ならジョブはディスパッチされない(): void
    {
        Queue::fake();

        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::DailyReportSubmitted->value,
            'enabled' => false,
        ]);

        $this->actingAs($this->student)
            ->post('/daily-reports', [
                'curriculum_id' => $this->curriculum->id,
                'reported_on' => now()->format('Y-m-d'),
                'understanding_level' => 3,
                'content' => '今日の学習内容',
                'impression' => '良かった',
            ]);

        Queue::assertNotPushed(SendSlackNotificationJob::class);
    }

    /** 通知設定レコードが存在しないならジョブはディスパッチされない */
    public function test_通知設定レコード未設定ならジョブはディスパッチされない(): void
    {
        Queue::fake();

        $this->actingAs($this->student)
            ->post('/daily-reports', [
                'curriculum_id' => $this->curriculum->id,
                'reported_on' => now()->format('Y-m-d'),
                'understanding_level' => 3,
                'content' => '今日の学習内容',
                'impression' => '良かった',
            ]);

        Queue::assertNotPushed(SendSlackNotificationJob::class);
    }

    /** 講師コメント追加でジョブがディスパッチされる */
    public function test_コメント追加で通知ジョブがディスパッチされる(): void
    {
        Queue::fake();

        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::CommentAdded->value,
            'enabled' => true,
        ]);

        $report = DailyReport::factory()->create([
            'user_id' => $this->student->id,
            'curriculum_id' => $this->curriculum->id,
        ]);

        $this->actingAs($this->instructor)
            ->post("/daily-reports/{$report->id}/comments", [
                'body' => 'コメント内容',
            ]);

        Queue::assertPushed(SendSlackNotificationJob::class);
    }

    /** お知らせ投稿（公開済み）でジョブがディスパッチされる */
    public function test_お知らせ投稿で通知ジョブがディスパッチされる(): void
    {
        Queue::fake();

        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::AnnouncementPosted->value,
            'enabled' => true,
        ]);

        $this->actingAs($this->admin)
            ->post('/announcements', [
                'title' => 'テストお知らせ',
                'body' => 'お知らせ本文',
                'priority' => 'normal',
                'target_type' => 'all',
                'target_id' => null,
                'publish_now' => true,
            ]);

        Queue::assertPushed(SendSlackNotificationJob::class);
    }

    /** Organization::isSlackEnabled が正しく動作する */
    public function test_isSlackEnabledがWebhookURL有りでtrueを返す(): void
    {
        $this->assertTrue($this->org->isSlackEnabled());
    }

    /** Organization::isSlackEnabled がURL未設定でfalseを返す */
    public function test_isSlackEnabledがWebhookURL無しでfalseを返す(): void
    {
        $org = Organization::factory()->create(['slack_webhook_url' => null]);
        $this->assertFalse($org->isSlackEnabled());
    }

    /** NotificationSetting が Organization に belongsTo している */
    public function test_NotificationSettingがOrganizationに属している(): void
    {
        $setting = NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::RiskDetected->value,
        ]);

        $this->assertEquals($this->org->id, $setting->organization->id);
    }

    /** Organization が notificationSettings を hasMany している */
    public function test_OrganizationがnotificationSettingsを持つ(): void
    {
        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::DailyReportSubmitted->value,
        ]);

        NotificationSetting::factory()->create([
            'organization_id' => $this->org->id,
            'event_type' => NotificationEventType::CommentAdded->value,
        ]);

        $this->assertCount(2, $this->org->notificationSettings);
    }
}
