<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Support\Carbon;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, Submission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $submission->test->curriculum->hasInstructor($user->id);
        }

        return $user->id === $submission->user_id;
    }

    public function create(User $user, Test $test): bool
    {
        if (!$user->isStudent()) {
            return false;
        }

        if (!$test->isAvailableFor(Carbon::now())) {
            return false;
        }

        $remaining = $test->remainingAttempts($user->id);

        // null = 無制限、0 = 残り回数なし
        return $remaining === null || $remaining > 0;
    }
}
