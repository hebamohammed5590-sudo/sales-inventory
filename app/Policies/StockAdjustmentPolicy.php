<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\StockAdjustment;
use App\Models\User;

class StockAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManageStock($user);
    }

    public function view(
        User $user,
        StockAdjustment $stockAdjustment
    ): bool {
        return $this->canManageStock($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageStock($user);
    }

    public function update(
        User $user,
        StockAdjustment $stockAdjustment
    ): bool {
        return false;
    }

    public function delete(
        User $user,
        StockAdjustment $stockAdjustment
    ): bool {
        return false;
    }

    private function canManageStock(User $user): bool
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
