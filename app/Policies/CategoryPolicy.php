<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageCategories($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->canManageCategories($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCategories($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->canManageCategories($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->canManageCategories($user);
    }

    private function canManageCategories(User $user): bool
    {
        return in_array(
            $user->role,
            [
                Role::Admin,
                Role::Manager,
            ],
            true
        );
    }
}
