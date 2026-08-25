<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageSuppliers($user);
    }

    public function view(
        User $user,
        Supplier $supplier
    ): bool {
        return $this->canManageSuppliers($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageSuppliers($user);
    }

    public function update(
        User $user,
        Supplier $supplier
    ): bool {
        return $this->canManageSuppliers($user);
    }

    public function delete(
        User $user,
        Supplier $supplier
    ): bool {
        return $this->canManageSuppliers($user);
    }

    private function canManageSuppliers(User $user): bool
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
