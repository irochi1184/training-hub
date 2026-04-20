<?php

namespace App\Policies;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Support\Carbon;

class DailyReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function view(User $user, DailyReport $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $report->cohort->instructor_id === $user->id;
        }

        return $user->id === $report->user_id;
    }

    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function update(User $user, DailyReport $report): bool
    {
        if (!$user->isStudent()) {
            return false;
        }

        if ($user->id !== $report->user_id) {
            return false;
        }

        return $report->reported_on->isToday();
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
