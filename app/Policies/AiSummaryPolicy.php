<?php

namespace App\Policies;

use App\Models\AiSummary;
use App\Models\User;

class AiSummaryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, AiSummary $summary): bool
    {
        if ($user->isAdmin()) {
            return $user->organization_id === $summary->organization_id;
        }

        if ($user->isInstructor()) {
            return $user->organization_id === $summary->organization_id;
        }

        return false;
    }

    public function generate(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }
}
