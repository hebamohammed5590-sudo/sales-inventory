<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ProductReturn;
use App\Models\User;

class ProductReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageSales($user);
    }

    public function view(
        User $user,
        ProductReturn $productReturn
    ): bool {
        return $this->canManageSales($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSales($user);
    }

    public function update(
        User $user,
        ProductReturn $productReturn
    ): bool {
        return false;
    }

    public function delete(
        User $user,
        ProductReturn $productReturn
    ): bool {
        return false;
    }

    private function canManageSales(User $user): bool
    {
        return in_array(
            $user->role,
            [
                Role::Admin,
                Role::Manager,
                Role::Cashier,
            ],
            true
        );
    }
}
