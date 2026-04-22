<?php

namespace App\Policies;

use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Models\User;

class DailyReportCommentPolicy
{
    public function create(User $user, DailyReport $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $report->curriculum->instructor_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, DailyReportComment $comment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $user->id === $comment->user_id;
        }

        return false;
    }
}
