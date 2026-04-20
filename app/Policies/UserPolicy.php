<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return in_array($authUser->role, [UserRole::Admin, UserRole::Instructor], true);
    }

    public function view(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        if ($authUser->isInstructor()) {
            return $this->instructorManagesStudent($authUser, $targetUser);
        }

        return $authUser->id === $targetUser->id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        if ($authUser->isStudent()) {
            return $authUser->id === $targetUser->id;
        }

        return false;
    }

    public function delete(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    private function instructorManagesStudent(User $instructor, User $student): bool
    {
        if (!$student->isStudent()) {
            return false;
        }

        return $instructor->instructedCohorts()
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', $student->id))
            ->exists();
    }
}
