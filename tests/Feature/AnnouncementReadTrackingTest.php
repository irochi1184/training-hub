<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnnouncementReadTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_一括既読で全お知らせが既読になる(): void
    {
        $student = User::factory()->student()->create();

        Announcement::factory()->count(3)->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'all',
            'published_at' => Carbon::now()->subHour(),
        ]);

        $this->assertDatabaseCount('announcement_reads', 0);

        $response = $this->actingAs($student)->post('/announcements/mark-all-read');

        $response->assertRedirect('/announcements');
        $this->assertDatabaseCount('announcement_reads', 3);
    }

    public function test_既読済みは重複登録されない(): void
    {
        $student = User::factory()->student()->create();

        $announcement = Announcement::factory()->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'all',
            'published_at' => Carbon::now()->subHour(),
        ]);

        // 事前に1件既読
        AnnouncementRead::create([
            'announcement_id' => $announcement->id,
            'user_id' => $student->id,
            'read_at' => Carbon::now(),
        ]);

        // 追加で2件作成
        Announcement::factory()->count(2)->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'all',
            'published_at' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($student)->post('/announcements/mark-all-read');

        // 既読1 + 新規2 = 3
        $this->assertDatabaseCount('announcement_reads', 3);
    }

    public function test_未読フィルターで未読のみ表示される(): void
    {
        $admin = User::factory()->admin()->create();

        $read = Announcement::factory()->create([
            'organization_id' => $admin->organization_id,
            'published_at' => Carbon::now()->subHour(),
        ]);
        Announcement::factory()->create([
            'organization_id' => $admin->organization_id,
            'published_at' => Carbon::now()->subHour(),
        ]);

        // 1件だけ既読
        AnnouncementRead::create([
            'announcement_id' => $read->id,
            'user_id' => $admin->id,
            'read_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($admin)->get('/announcements?filter=unread');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Announcements/Index')
            ->has('announcements.data', 1) // 未読1件のみ
            ->where('filter', 'unread')
        );
    }

    public function test_フィルターなしで全件表示される(): void
    {
        $admin = User::factory()->admin()->create();

        Announcement::factory()->count(3)->create([
            'organization_id' => $admin->organization_id,
            'published_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->actingAs($admin)->get('/announcements');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Announcements/Index')
            ->has('announcements.data', 3)
        );
    }

    public function test_一括既読後に未読バッジが0になる(): void
    {
        $student = User::factory()->student()->create();

        Announcement::factory()->count(2)->create([
            'organization_id' => $student->organization_id,
            'target_type' => 'all',
            'published_at' => Carbon::now()->subHour(),
        ]);

        $this->actingAs($student)->post('/announcements/mark-all-read');

        $response = $this->actingAs($student)->get('/announcements');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('readIds', function ($readIds) {
                return count($readIds) === 2;
            })
        );
    }
}
