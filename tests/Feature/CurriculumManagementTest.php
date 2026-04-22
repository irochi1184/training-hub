<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CurriculumManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_adminがカリキュラム一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        Curriculum::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/curricula');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Curricula/Index')
            ->has('curricula.data', 3)
        );
    }

    public function test_instructorがカリキュラム一覧にアクセスすると403(): void
    {
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($instructor)->get('/curricula');

        $response->assertStatus(403);
    }

    public function test_studentがカリキュラム一覧にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/curricula');

        $response->assertStatus(403);
    }

    public function test_adminがカリキュラムを作成できる(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => '新カリキュラム',
            'instructor_id' => $instructor->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertRedirect('/curricula');
        $this->assertDatabaseHas('curricula', [
            'name' => '新カリキュラム',
            'instructor_id' => $instructor->id,
            'organization_id' => $admin->organization_id,
        ]);
    }

    public function test_終了日が開始日より前だとバリデーションエラー(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => 'テスト',
            'instructor_id' => $instructor->id,
            'starts_on' => '2026-07-31',
            'ends_on' => '2026-05-01',
        ]);

        $response->assertSessionHasErrors('ends_on');
    }

    public function test_講師ロールでないユーザーは担当者に設定できない(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => 'テスト',
            'instructor_id' => $student->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertSessionHasErrors('instructor_id');
    }

    public function test_adminがカリキュラムを更新できる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create(['name' => '旧名称']);
        $newInstructor = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->put("/curricula/{$curriculum->id}", [
            'name' => '新名称',
            'instructor_id' => $newInstructor->id,
            'starts_on' => $curriculum->starts_on->toDateString(),
            'ends_on' => $curriculum->ends_on->toDateString(),
        ]);

        $response->assertRedirect('/curricula');
        $this->assertDatabaseHas('curricula', [
            'id' => $curriculum->id,
            'name' => '新名称',
            'instructor_id' => $newInstructor->id,
        ]);
    }

    public function test_adminがカリキュラムを削除するとsoft_deleteされる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        $response = $this->actingAs($admin)->delete("/curricula/{$curriculum->id}");

        $response->assertRedirect('/curricula');
        $this->assertSoftDeleted('curricula', ['id' => $curriculum->id]);
    }

    public function test_instructorはカリキュラムを作成できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $anotherInstructor = User::factory()->instructor()->create();

        $response = $this->actingAs($instructor)->post('/curricula', [
            'name' => 'テスト',
            'instructor_id' => $anotherInstructor->id,
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertStatus(403);
    }
}
