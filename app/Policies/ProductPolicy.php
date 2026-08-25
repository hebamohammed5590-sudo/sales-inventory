<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    public function view(User $user, Product $product): bool
    {
        return $this->canManageProducts($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageProducts($user);
    }

    public function update(
        User $user,
        Product $product
    ): bool {
        return $this->canManageProducts($user);
    }

    public function delete(
        User $user,
        Product $product
    ): bool {
        return $this->canManageProducts($user);
    }

    private function canManageProducts(User $user): bool
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
