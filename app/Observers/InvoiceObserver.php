<?php

namespace App\Observers;

use App\Enums\Role;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

class InvoiceObserver
{
    public function created(
        Invoice $invoice
    ): void {
        $this->clearDashboardCache();
    }

    public function updated(
        Invoice $invoice
    ): void {
        $this->clearDashboardCache();
    }

    public function deleted(
        Invoice $invoice
    ): void {
        $this->clearDashboardCache();
    }

    public function restored(
        Invoice $invoice
    ): void {
        $this->clearDashboardCache();
    }

    private function clearDashboardCache(): void
    {
        foreach (Role::cases() as $role) {
            Cache::forget(
                sprintf(
                    'dashboard.data.%s',
                    $role->value
                )
            );
        }
    }
}
