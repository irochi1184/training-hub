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
            return $submission->test->cohort->instructor_id === $user->id;
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

        return !$test->submissions()->where('user_id', $user->id)->exists();
    }
}
