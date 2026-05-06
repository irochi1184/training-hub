<?php

namespace Tests\Feature;

use App\Enums\RiskAlertReason;
use App\Models\Curriculum;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RiskAlertTest extends TestCase
{
    use RefreshDatabase;

    /** admin が要注意者一覧を閲覧できる */
    public function test_adminが要注意者一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/risk-alerts');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('RiskAlerts/Index'));
    }

    /** instructor が担当カリキュラムのアラートを解消できる */
    public function test_instructorがアラートを解消できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $alert = RiskAlert::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->patch("/risk-alerts/{$alert->id}/resolve");

        $response->assertRedirect();
        $this->assertNotNull($alert->fresh()->resolved_at);
    }

    /** student が要注意者一覧にアクセスすると 403 */
    public function test_studentが要注意者一覧にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/risk-alerts');

        $response->assertStatus(403);
    }

    /** reason クエリで理由別に絞り込める */
    public function test_reason絞り込みが適用される(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        $matching = RiskAlert::factory()->create([
            'curriculum_id' => $curriculum->id,
            'reason' => RiskAlertReason::ReportMissing->value,
            'resolved_at' => null,
        ]);
        $other = RiskAlert::factory()->create([
            'curriculum_id' => $curriculum->id,
            'reason' => RiskAlertReason::LowUnderstanding->value,
            'resolved_at' => null,
        ]);

        $response = $this->actingAs($admin)->get('/risk-alerts?reason=report_missing');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('RiskAlerts/Index')
            ->has('alerts.data', 1)
            ->where('alerts.data.0.id', $matching->id)
            ->where('filters.reason', 'report_missing')
        );
    }

    /** curriculum_id クエリでカリキュラム別に絞り込める */
    public function test_curriculum絞り込みが適用される(): void
    {
        $admin = User::factory()->admin()->create();
        $targetCurriculum = Curriculum::factory()->create();
        $otherCurriculum = Curriculum::factory()->create();

        $matching = RiskAlert::factory()->create(['curriculum_id' => $targetCurriculum->id, 'resolved_at' => null]);
        RiskAlert::factory()->create(['curriculum_id' => $otherCurriculum->id, 'resolved_at' => null]);

        $response = $this->actingAs($admin)->get("/risk-alerts?curriculum_id={$targetCurriculum->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('alerts.data', 1)
            ->where('alerts.data.0.id', $matching->id)
        );
    }

    /** instructor には担当カリキュラムのみが curricula プロパティに含まれる */
    public function test_instructorのカリキュラム選択肢は担当分のみ(): void
    {
        $instructor = User::factory()->instructor()->create();
        $mine = Curriculum::factory()->create();
        $mine->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $others = Curriculum::factory()->create();

        $response = $this->actingAs($instructor)->get('/risk-alerts');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('curricula', 1)
            ->where('curricula.0.id', $mine->id)
        );
    }

    /** 解消済みアラートを再度解消しようとするとエラーメッセージが返る */
    public function test_解消済みアラートを再度解消するとエラー(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $alert = RiskAlert::factory()->resolved()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->patch("/risk-alerts/{$alert->id}/resolve");

        $response->assertRedirect();
        $response->assertSessionHasErrors(['error']);
    }

    /** instructor が担当外カリキュラムのアラートを解消しようとすると 403 */
    public function test_instructorが担当外カリキュラムのアラートを解消しようとすると403(): void
    {
        $instructor = User::factory()->instructor()->create();
        $otherCurriculum = Curriculum::factory()->create();
        $alert = RiskAlert::factory()->create(['curriculum_id' => $otherCurriculum->id]);

        $response = $this->actingAs($instructor)->patch("/risk-alerts/{$alert->id}/resolve");

        $response->assertStatus(403);
        $this->assertNull($alert->fresh()->resolved_at);
    }
}
