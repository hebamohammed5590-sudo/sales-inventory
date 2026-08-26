<?php

namespace App\Observers;

use App\Enums\InvoiceStatus;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\InvoiceFullyPaidNotification;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Notification;

class PaymentObserver
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function created(
        Payment $payment
    ): void {
        $payable = $payment->payable;

        if (! $payable instanceof Invoice) {
            return;
        }

        $actor = $payment->user;

        if ($actor) {
            $this->activityLogService->record(
                actor: $actor,
                action: 'payment.recorded',
                subject: $payable,
                description: sprintf(
                    '%s recorded a payment of %s for invoice %s.',
                    $actor->name,
                    money(
                        (int) $payment->getRawOriginal('amount')
                    ),
                    $payable->invoice_number
                ),
                properties: [
                    'payment_id' => $payment->id,
                    'amount_in_cents' => (int) $payment->getRawOriginal('amount'),
                    'method' => $payment->method->value,
                ]
            );
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