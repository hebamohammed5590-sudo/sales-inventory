<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Payment::observe(
            PaymentObserver::class
        );

        Invoice::observe(
            InvoiceObserver::class
        );

        Gate::define(
            'view-reports',
            function (User $user): bool {
                return in_array(
                    $user->role,
                    [
                        Role::Admin,
                        Role::Manager,
                    ],
                    true
                );
            }
        );
    }
}
