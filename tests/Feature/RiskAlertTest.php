<?php

namespace Tests\Feature;

use App\Models\Cohort;
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

    /** instructor が担当コホートのアラートを解消できる */
    public function test_instructorがアラートを解消できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        $alert = RiskAlert::factory()->create(['cohort_id' => $cohort->id]);

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
}
