<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\CurriculumInstructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CurriculumManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createCurriculumWithInstructor(?User $instructor = null): Curriculum
    {
        $instructor ??= User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->attach($instructor->id, ['role' => 'main']);

        return $curriculum;
    }

    public function test_adminがカリキュラム一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $this->createCurriculumWithInstructor();
        $this->createCurriculumWithInstructor();
        $this->createCurriculumWithInstructor();

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
            'main_instructor_ids' => [$instructor->id],
            'sub_instructor_ids' => [],
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertRedirect('/curricula');
        $this->assertDatabaseHas('curricula', [
            'name' => '新カリキュラム',
            'organization_id' => $admin->organization_id,
        ]);
        $this->assertDatabaseHas('curriculum_instructors', [
            'user_id' => $instructor->id,
            'role' => 'main',
        ]);
    }

    public function test_メインとサブ講師を同時に登録できる(): void
    {
        $admin = User::factory()->admin()->create();
        $mainInstructor = User::factory()->instructor()->create();
        $subInstructor = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => '複数講師カリキュラム',
            'main_instructor_ids' => [$mainInstructor->id],
            'sub_instructor_ids' => [$subInstructor->id],
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertRedirect('/curricula');

        $curriculum = Curriculum::where('name', '複数講師カリキュラム')->first();
        $this->assertNotNull($curriculum);
        $this->assertCount(1, $curriculum->mainInstructors);
        $this->assertCount(1, $curriculum->subInstructors);
        $this->assertEquals($mainInstructor->id, $curriculum->mainInstructors->first()->id);
        $this->assertEquals($subInstructor->id, $curriculum->subInstructors->first()->id);
    }

    public function test_メイン講師を複数人登録できる(): void
    {
        $admin = User::factory()->admin()->create();
        $main1 = User::factory()->instructor()->create();
        $main2 = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => 'メイン複数',
            'main_instructor_ids' => [$main1->id, $main2->id],
            'sub_instructor_ids' => [],
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertRedirect('/curricula');
        $curriculum = Curriculum::where('name', 'メイン複数')->first();
        $this->assertCount(2, $curriculum->mainInstructors);
    }

    public function test_終了日が開始日より前だとバリデーションエラー(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => 'テスト',
            'main_instructor_ids' => [$instructor->id],
            'starts_on' => '2026-07-31',
            'ends_on' => '2026-05-01',
        ]);

        $response->assertSessionHasErrors('ends_on');
    }

    public function test_講師ロールでないユーザーはメイン講師に設定できない(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => 'テスト',
            'main_instructor_ids' => [$student->id],
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertSessionHasErrors('main_instructor_ids.0');
    }

    public function test_メイン講師が空だとバリデーションエラー(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/curricula', [
            'name' => 'テスト',
            'main_instructor_ids' => [],
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertSessionHasErrors('main_instructor_ids');
    }

    public function test_adminがカリキュラムを更新できる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = $this->createCurriculumWithInstructor();
        $newInstructor = User::factory()->instructor()->create();

        $response = $this->actingAs($admin)->put("/curricula/{$curriculum->id}", [
            'name' => '新名称',
            'main_instructor_ids' => [$newInstructor->id],
            'sub_instructor_ids' => [],
            'starts_on' => $curriculum->starts_on->toDateString(),
            'ends_on' => $curriculum->ends_on->toDateString(),
        ]);

        $response->assertRedirect('/curricula');
        $this->assertDatabaseHas('curricula', [
            'id' => $curriculum->id,
            'name' => '新名称',
        ]);
        $this->assertDatabaseHas('curriculum_instructors', [
            'curriculum_id' => $curriculum->id,
            'user_id' => $newInstructor->id,
            'role' => 'main',
        ]);
    }

    public function test_adminがカリキュラムを削除するとsoft_deleteされる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = $this->createCurriculumWithInstructor();

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
            'main_instructor_ids' => [$anotherInstructor->id],
            'starts_on' => '2026-05-01',
            'ends_on' => '2026-07-31',
        ]);

        $response->assertStatus(403);
    }
}
