<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** ログイン画面が表示される */
    public function test_ログイン画面が表示される(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    /** 正しい認証情報でログインするとダッシュボードにリダイレクトされる */
    public function test_正しい認証情報でログインできる(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** 間違った認証情報ではログインできない */
    public function test_間違った認証情報でログインできない(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /** ログアウトすると /login にリダイレクトされる */
    public function test_ログアウトできる(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** 未認証で /dashboard にアクセスすると /login にリダイレクトされる */
    public function test_未認証でダッシュボードにアクセスするとログインにリダイレクトされる(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}
