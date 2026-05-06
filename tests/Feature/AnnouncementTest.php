<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_adminがお知らせ一覧を表示できる(): void
    {
        $admin = User::factory()->admin()->create();
        Announcement::factory()->create(['organization_id' => $admin->organization_id]);

        $response = $this->actingAs($admin)->get('/announcements');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Announcements/Index')
            ->has('announcements.data', 1)
        );
    }

    public function test_studentが自分宛のお知らせのみ表示される(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create(['organization_id' => $student->organization_id]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        // 全員宛（表示される）
        Announcement::factory()->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'all',
            'published_at' => now(),
        ]);

        // カリキュラム宛（表示される）
        Announcement::factory()->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'curriculum',
            'target_id' => $curriculum->id,
            'published_at' => now(),
        ]);

        // 別のカリキュラム宛（表示されない）
        $otherCurriculum = Curriculum::factory()->create(['organization_id' => $student->organization_id]);
        Announcement::factory()->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'curriculum',
            'target_id' => $otherCurriculum->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($student)->get('/announcements');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->has('announcements.data', 2)
        );
    }

    public function test_adminがお知らせを作成できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/announcements', [
            'title' => 'テストお知らせ',
            'body' => 'お知らせの本文です。',
            'priority' => 'normal',
            'target_type' => 'all',
            'target_id' => null,
            'publish_now' => true,
        ]);

        $response->assertRedirect('/announcements');
        $this->assertDatabaseHas('announcements', [
            'title' => 'テストお知らせ',
            'target_type' => 'all',
            'created_by' => $admin->id,
        ]);
    }

    public function test_instructorが担当カリキュラム宛にお知らせを作成できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create([
            'organization_id' => $instructor->organization_id,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->actingAs($instructor)->post('/announcements', [
            'title' => '講師からのお知らせ',
            'body' => '次回の授業について',
            'priority' => 'important',
            'target_type' => 'curriculum',
            'target_id' => $curriculum->id,
            'publish_now' => true,
        ]);

        $response->assertRedirect('/announcements');
        $this->assertDatabaseHas('announcements', [
            'title' => '講師からのお知らせ',
            'priority' => 'important',
            'target_type' => 'curriculum',
            'target_id' => $curriculum->id,
        ]);
    }

    public function test_studentはお知らせを作成できない(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->post('/announcements', [
            'title' => '不正作成',
            'body' => '本文',
            'priority' => 'normal',
            'target_type' => 'all',
            'publish_now' => true,
        ]);

        $response->assertForbidden();
    }

    public function test_お知らせ詳細を表示すると既読になる(): void
    {
        $student = User::factory()->student()->create();
        $announcement = Announcement::factory()->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'all',
            'published_at' => now(),
        ]);

        $this->assertDatabaseMissing('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($student)->get("/announcements/{$announcement->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_adminがお知らせを編集できる(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'organization_id' => $admin->organization_id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->put("/announcements/{$announcement->id}", [
            'title' => '更新後タイトル',
            'body' => '更新後本文',
            'priority' => 'important',
            'target_type' => 'all',
            'publish_now' => true,
        ]);

        $response->assertRedirect('/announcements');
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => '更新後タイトル',
            'priority' => 'important',
        ]);
    }

    public function test_adminがお知らせを削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $announcement = Announcement::factory()->create([
            'organization_id' => $admin->organization_id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete("/announcements/{$announcement->id}");

        $response->assertRedirect('/announcements');
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_instructorは他者作成のお知らせを削除できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $admin = User::factory()->admin()->create(['organization_id' => $instructor->organization_id]);
        $announcement = Announcement::factory()->create([
            'organization_id' => $instructor->organization_id,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($instructor)->delete("/announcements/{$announcement->id}");

        $response->assertForbidden();
    }

    public function test_バリデーションエラーでタイトル必須(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/announcements', [
            'title' => '',
            'body' => '本文',
            'priority' => 'normal',
            'target_type' => 'all',
            'publish_now' => true,
        ]);

        $response->assertSessionHasErrors('title');
    }
}
