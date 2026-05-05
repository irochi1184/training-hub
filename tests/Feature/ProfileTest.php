<?php

namespace Tests\Feature;

use App\Models\StudentProfile;
use App\Models\StudentSkill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_studentがプロフィール画面を表示できる(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->where('profile', null)
        );
    }

    public function test_プロフィール設定済みの場合データが表示される(): void
    {
        $student = User::factory()->student()->create();
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'bio' => 'テスト自己紹介',
            'learning_goal' => 'テスト学習目標',
        ]);
        StudentSkill::create([
            'student_profile_id' => $profile->id,
            'skill_name' => 'PHP',
            'level' => 2,
        ]);

        $response = $this->actingAs($student)->get('/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->where('profile.bio', 'テスト自己紹介')
            ->where('profile.learning_goal', 'テスト学習目標')
            ->has('profile.skills', 1)
        );
    }

    public function test_studentがプロフィール編集画面を表示できる(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/profile/edit');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Profile/Edit'));
    }

    public function test_studentがプロフィールを新規作成できる(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->put('/profile', [
            'bio' => '新しい自己紹介',
            'learning_goal' => '3ヶ月でWebアプリを作る',
            'skills' => [
                ['skill_name' => 'HTML', 'level' => 1],
                ['skill_name' => 'CSS', 'level' => 2],
            ],
        ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'bio' => '新しい自己紹介',
            'learning_goal' => '3ヶ月でWebアプリを作る',
        ]);
        $this->assertDatabaseCount('student_skills', 2);
    }

    public function test_studentがプロフィールを更新できる(): void
    {
        $student = User::factory()->student()->create();
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'bio' => '古い自己紹介',
            'learning_goal' => '古い目標',
        ]);
        StudentSkill::create([
            'student_profile_id' => $profile->id,
            'skill_name' => 'PHP',
            'level' => 1,
        ]);

        $response = $this->actingAs($student)->put('/profile', [
            'bio' => '更新済み自己紹介',
            'learning_goal' => '更新済み目標',
            'skills' => [
                ['skill_name' => 'Laravel', 'level' => 3],
            ],
        ]);

        $response->assertRedirect('/profile');
        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'bio' => '更新済み自己紹介',
        ]);
        $this->assertDatabaseCount('student_skills', 1);
        $this->assertDatabaseHas('student_skills', [
            'skill_name' => 'Laravel',
            'level' => 3,
        ]);
    }

    public function test_bioが1000文字を超えるとバリデーションエラー(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->put('/profile', [
            'bio' => str_repeat('あ', 1001),
            'learning_goal' => '',
            'skills' => [],
        ]);

        $response->assertSessionHasErrors('bio');
    }

    public function test_スキルが10個を超えるとバリデーションエラー(): void
    {
        $student = User::factory()->student()->create();
        $skills = array_map(fn ($i) => ['skill_name' => "Skill{$i}", 'level' => 1], range(1, 11));

        $response = $this->actingAs($student)->put('/profile', [
            'bio' => '',
            'learning_goal' => '',
            'skills' => $skills,
        ]);

        $response->assertSessionHasErrors('skills');
    }

    public function test_admin_instructorはプロフィールページにアクセスできない(): void
    {
        $admin = User::factory()->admin()->create();
        $instructor = User::factory()->instructor()->create();

        $this->actingAs($admin)->get('/profile')->assertStatus(403);
        $this->actingAs($instructor)->get('/profile')->assertStatus(403);
    }

    public function test_受講生詳細画面でプロフィールが閲覧できる(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['organization_id' => $admin->organization_id]);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'bio' => '詳細ページ確認用',
            'learning_goal' => null,
        ]);

        $response = $this->actingAs($admin)->get("/students/{$student->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Students/Show')
            ->where('student.student_profile.bio', '詳細ページ確認用')
        );
    }
}
