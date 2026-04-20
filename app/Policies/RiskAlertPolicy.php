<?php

namespace App\Policies;

use App\Models\RiskAlert;
use App\Models\User;

class RiskAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function resolve(User $user, RiskAlert $alert): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $alert->cohort->instructor_id === $user->id;
        }

        return false;
    }
}
