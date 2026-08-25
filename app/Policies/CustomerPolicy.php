<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
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

    public function view(
        User $user,
        Customer $customer
    ): bool {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageCustomers($user);
    }

    public function update(
        User $user,
        Customer $customer
    ): bool {
        return $this->canManageCustomers($user);
    }

    public function delete(
        User $user,
        Customer $customer
    ): bool {
        return $this->canManageCustomers($user);
    }

    private function canManageCustomers(User $user): bool
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
