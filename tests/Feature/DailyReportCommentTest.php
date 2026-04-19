<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportCommentTest extends TestCase
{
    use RefreshDatabase;

    /** instructor が担当コホートの日報にコメントを追加できる */
    public function test_instructorが日報にコメントを追加できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        $report = DailyReport::factory()->create(['cohort_id' => $cohort->id]);

        $response = $this->actingAs($instructor)->post("/daily-reports/{$report->id}/comments", [
            'body' => 'よく理解できていますね。',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_report_comments', [
            'daily_report_id' => $report->id,
            'user_id' => $instructor->id,
            'body' => 'よく理解できていますね。',
        ]);
    }

    /** student がコメントを追加しようとすると 403 */
    public function test_studentがコメントを追加しようとすると403(): void
    {
        $student = User::factory()->student()->create();
        $cohort = Cohort::factory()->create();
        $report = DailyReport::factory()->create(['cohort_id' => $cohort->id]);

        $response = $this->actingAs($student)->post("/daily-reports/{$report->id}/comments", [
            'body' => 'コメントです。',
        ]);

        $response->assertStatus(403);
    }

    /** instructor が自分のコメントを削除できる */
    public function test_instructorが自分のコメントを削除できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        $report = DailyReport::factory()->create(['cohort_id' => $cohort->id]);
        $comment = DailyReportComment::factory()->create([
            'daily_report_id' => $report->id,
            'user_id' => $instructor->id,
        ]);

        $response = $this->actingAs($instructor)
            ->delete("/daily-reports/{$report->id}/comments/{$comment->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('daily_report_comments', ['id' => $comment->id]);
    }
}
