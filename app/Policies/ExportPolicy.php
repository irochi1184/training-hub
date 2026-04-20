<?php

namespace App\Policies;

use App\Models\User;

class ExportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function exportDailyReports(User $user): bool
    {
        return $user->isAdmin();
    }

    public function exportTestResults(User $user): bool
    {
        return $user->isAdmin();
    }
}
