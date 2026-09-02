<?php

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
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
        Invoice $invoice
    ): bool {
        if ($invoice->isPurchase()) {
            return $this->canManagePurchases($user);
        }

        return $this->canManageSales($user);
    }

    public function create(
        User $user,
        InvoiceType $type
    ): bool {
        if ($type === InvoiceType::Purchase) {
            return $this->canManagePurchases($user);
        }

        return $this->canManageSales($user);
    }

    public function update(
        User $user,
        Invoice $invoice
    ): bool {
        if (! $invoice->isDraft()) {
            return false;
        }

        return $this->view(
            $user,
            $invoice
        );
    }

    public function confirm(
        User $user,
        Invoice $invoice
    ): bool {
        if (! $invoice->isDraft()) {
            return false;
        }

        return $this->view(
            $user,
            $invoice
        );
    }

    public function cancel(
        User $user,
        Invoice $invoice
    ): bool {
        if (
            ! $this->canManagePurchases($user)
            || $invoice->isCancelled()
        ) {
            return false;
        }

        if (
            $invoice->isSale()
            && $invoice->productReturns()->exists()
        ) {
            return false;
        }

        return $invoice->status->canTransitionTo(
            InvoiceStatus::Cancelled
        );
    }

    public function delete(
        User $user,
        Invoice $invoice
    ): bool {
        if (! $invoice->isDraft()) {
            return false;
        }

        return $this->canManagePurchases($user);
    }

    private function canManagePurchases(
        User $user
    ): bool {
        return in_array(
            $user->role,
            [
                Role::Admin,
                Role::Manager,
            ],
            true
        );
    }

    private function canManageSales(
        User $user
    ): bool {
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
