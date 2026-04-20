<?php

namespace Tests\Feature;

use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    /** admin が受講生一覧を閲覧できる */
    public function test_adminが受講生一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Students/Index'));
    }

    /** instructor が担当コホートの受講生を閲覧できる */
    public function test_instructorが担当コホートの受講生を閲覧できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $cohort = Cohort::factory()->create(['instructor_id' => $instructor->id]);
        User::factory()->student()->count(3)->create()->each(function (User $student) use ($cohort) {
            Enrollment::factory()->create([
                'user_id' => $student->id,
                'cohort_id' => $cohort->id,
            ]);
        });

        $response = $this->actingAs($instructor)->get('/students');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Students/Index'));
    }

    /** student が受講生一覧にアクセスすると 403 */
    public function test_studentが受講生一覧にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/students');

        $response->assertStatus(403);
    }

    /** admin が受講生詳細を閲覧できる */
    public function test_adminが受講生詳細を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($admin)->get("/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Students/Show'));
    }
}
