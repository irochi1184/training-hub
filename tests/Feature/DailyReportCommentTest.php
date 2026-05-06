<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportCommentTest extends TestCase
{
    use RefreshDatabase;

    /** instructor が担当カリキュラムの日報にコメントを追加できる */
    public function test_instructorが日報にコメントを追加できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $report = DailyReport::factory()->create(['curriculum_id' => $curriculum->id]);

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
        $curriculum = Curriculum::factory()->create();
        $report = DailyReport::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($student)->post("/daily-reports/{$report->id}/comments", [
            'body' => 'コメントです。',
        ]);

        $response->assertStatus(403);
    }

    /** instructor が自分のコメントを削除できる */
    public function test_instructorが自分のコメントを削除できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $report = DailyReport::factory()->create(['curriculum_id' => $curriculum->id]);
        $comment = DailyReportComment::factory()->create([
            'daily_report_id' => $report->id,
            'user_id' => $instructor->id,
        ]);

        $response = $this->actingAs($instructor)
            ->delete("/daily-reports/{$report->id}/comments/{$comment->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('daily_report_comments', ['id' => $comment->id]);
    }

    /** admin が日報にコメントを追加できる */
    public function test_adminが日報にコメントを追加できる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $report = DailyReport::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($admin)->post("/daily-reports/{$report->id}/comments", [
            'body' => '管理者からのコメントです。',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('daily_report_comments', [
            'daily_report_id' => $report->id,
            'user_id' => $admin->id,
            'body' => '管理者からのコメントです。',
        ]);
    }

    /** instructor が他人のコメントを削除しようとすると 403 */
    public function test_instructorが他人のコメントを削除しようとすると403(): void
    {
        $ownerInstructor = User::factory()->instructor()->create();
        $otherInstructor = User::factory()->instructor()->create();

        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$ownerInstructor->id => ['role' => 'main']]);
        $report = DailyReport::factory()->create(['curriculum_id' => $curriculum->id]);
        $comment = DailyReportComment::factory()->create([
            'daily_report_id' => $report->id,
            'user_id' => $ownerInstructor->id,
        ]);

        $response = $this->actingAs($otherInstructor)
            ->delete("/daily-reports/{$report->id}/comments/{$comment->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('daily_report_comments', ['id' => $comment->id]);
    }

    /** コメント本文が空だとバリデーションエラー */
    public function test_コメント本文が空だとバリデーションエラー(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $report = DailyReport::factory()->create(['curriculum_id' => $curriculum->id]);

        $response = $this->actingAs($instructor)->post("/daily-reports/{$report->id}/comments", [
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['body']);
    }
}
