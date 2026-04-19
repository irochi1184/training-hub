<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // テスト環境では Vite のビルド済み manifest を必要としない
        $this->withoutVite();

        // Inertia のページコンポーネント存在チェックを無効化する
        // （フロントエンドと切り離してバックエンドのみのテストを行うため）
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
