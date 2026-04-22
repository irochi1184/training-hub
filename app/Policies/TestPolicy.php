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

    public function view(User $user, Test $test): bool
    {
        if ($user->isAdmin() || $user->isInstructor()) {
            return true;
        }

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
            return $test->curriculum->instructor_id === $user->id;
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
            return $test->curriculum->instructor_id === $user->id;
        }

        return false;
    }
}
