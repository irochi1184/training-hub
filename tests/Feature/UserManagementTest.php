<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_adminがユーザー一覧を閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->instructor()->count(2)->create(['organization_id' => $admin->organization_id]);
        User::factory()->student()->count(3)->create(['organization_id' => $admin->organization_id]);

        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->has('users.data', 6) // admin + 2 instructors + 3 students
        );
    }

    public function test_ロールフィルターが機能する(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->instructor()->count(2)->create(['organization_id' => $admin->organization_id]);
        User::factory()->student()->count(3)->create(['organization_id' => $admin->organization_id]);

        $response = $this->actingAs($admin)->get('/users?role=instructor');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->has('users.data', 2)
        );
    }

    public function test_adminがユーザーを作成できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'テスト講師',
            'email' => 'new-instructor@example.com',
            'password' => 'password123',
            'role' => 'instructor',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'name' => 'テスト講師',
            'email' => 'new-instructor@example.com',
            'role' => 'instructor',
            'organization_id' => $admin->organization_id,
        ]);
    }

    public function test_adminがユーザーを更新できる(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->student()->create(['organization_id' => $admin->organization_id]);

        $response = $this->actingAs($admin)->put("/users/{$user->id}", [
            'name' => '更新済み',
            'email' => $user->email,
            'role' => 'instructor',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => '更新済み',
            'role' => 'instructor',
        ]);
    }

    public function test_adminがユーザーを削除できる(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->student()->create(['organization_id' => $admin->organization_id]);

        $response = $this->actingAs($admin)->delete("/users/{$user->id}");

        $response->assertRedirect('/users');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_instructorがユーザー管理にアクセスすると403(): void
    {
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($instructor)->get('/users');

        $response->assertStatus(403);
    }

    public function test_studentがユーザー管理にアクセスすると403(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/users');

        $response->assertStatus(403);
    }

    public function test_メールアドレス重複時にバリデーションエラー(): void
    {
        $admin = User::factory()->admin()->create();
        $existing = User::factory()->create(['organization_id' => $admin->organization_id]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'テスト',
            'email' => $existing->email,
            'password' => 'password123',
            'role' => 'student',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
