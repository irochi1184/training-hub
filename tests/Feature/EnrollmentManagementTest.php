<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_adminがエンロールメント管理画面を表示できる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        $response = $this->actingAs($admin)->get('/enrollments');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Enrollments/Index')
            ->has('curricula')
        );
    }

    public function test_instructorが担当カリキュラムのみ表示される(): void
    {
        $instructor = User::factory()->instructor()->create();
        $own = Curriculum::factory()->create();
        $own->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $other = Curriculum::factory()->create();

        $response = $this->actingAs($instructor)->get('/enrollments');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Enrollments/Index')
            ->has('curricula', 1)
        );
    }

    public function test_受講生をカリキュラムに登録できる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($admin)->post('/enrollments', [
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_一括登録ができる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $s1 = User::factory()->student()->create(['email' => 'bulk1@example.com']);
        $s2 = User::factory()->student()->create(['email' => 'bulk2@example.com']);

        $response = $this->actingAs($admin)->post('/enrollments/bulk', [
            'curriculum_id' => $curriculum->id,
            'emails' => "bulk1@example.com\nbulk2@example.com\nnonexist@example.com",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('enrollments', 2);
        $response->assertSessionHas('success');
    }

    public function test_受講登録を解除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($admin)->delete("/enrollments/{$enrollment->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    public function test_instructorは他のカリキュラムに登録できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $otherCurriculum = Curriculum::factory()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($instructor)->post('/enrollments', [
            'curriculum_id' => $otherCurriculum->id,
            'user_id' => $student->id,
        ]);

        $response->assertForbidden();
    }

    public function test_studentはエンロールメント管理にアクセスできない(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/enrollments');

        $response->assertForbidden();
    }

    public function test_重複登録は無視される(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();
        $student = User::factory()->student()->create();
        Enrollment::factory()->create([
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($admin)->post('/enrollments', [
            'curriculum_id' => $curriculum->id,
            'user_id' => $student->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('enrollments', 1);
    }
}
