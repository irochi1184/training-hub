<?php

namespace App\Policies;

use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Carbon;

class TestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * テスト詳細・分析画面の閲覧権限。
     * - admin: 常に許可
     * - instructor: 自分の担当カリキュラムのテストのみ
     * - student: 受験画面とは別。分析画面は不可
     */
    public function view(User $user, Test $test): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $test->curriculum->hasInstructor($user->id);
        }

        // student は受験可否チェック（分析画面では authorize('view') を呼ばないので実質到達しない）
        return $test->isAvailableFor(Carbon::now());
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Test $test): bool
    {
        if ($test->hasSubmissions()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $test->curriculum->hasInstructor($user->id);
        }

        return false;
    }

    public function delete(User $user, Test $test): bool
    {
        if ($test->hasSubmissions()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $test->curriculum->hasInstructor($user->id);
        }

        return false;
    }
}
