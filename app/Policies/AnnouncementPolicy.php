<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $announcement->created_by === $user->id
                || $this->isTargetedTo($user, $announcement);
        }

        return $this->isTargetedTo($user, $announcement);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isInstructor();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor() && $announcement->created_by === $user->id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInstructor() && $announcement->created_by === $user->id;
    }

    private function isTargetedTo(User $user, Announcement $announcement): bool
    {
        if (!$announcement->isPublished()) {
            return false;
        }

        return match ($announcement->target_type->value) {
            'all' => true,
            'curriculum' => $user->enrollments()
                ->where('curriculum_id', $announcement->target_id)
                ->exists(),
            'user' => $announcement->target_id === $user->id,
            default => false,
        };
    }
}
