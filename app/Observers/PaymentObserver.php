<?php

namespace App\Observers;

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\InvoiceFullyPaidNotification;
use Illuminate\Support\Facades\Notification;

class PaymentObserver
{
    public function created(
        Payment $payment
    ): void {
        $payable = $payment->payable;

        if (! $payable instanceof Invoice) {
            return;
        }

        $total = (int) $payable->getRawOriginal(
            'total'
        );

        $paidAmount = $payable->paidAmountInCents();

        if ($paidAmount >= $total) {
            $payable->update([
                'status' => InvoiceStatus::Paid,
            ]);

            if ($payable->isSale()) {
                $this->notifyInvoiceFullyPaid(
                    $payable
                );
            }

            return;
        }

        if ($paidAmount > 0) {
            $payable->update([
                'status' => InvoiceStatus::PartiallyPaid,
            ]);
        }
    }

    private function notifyInvoiceFullyPaid(
        Invoice $invoice
    ): void {
        $recipients = User::query()
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'role',
                [
                    Role::Admin->value,
                    Role::Manager->value,
                ]
            )
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,

            new InvoiceFullyPaidNotification(
                $invoice
            )
        );
    }
}
