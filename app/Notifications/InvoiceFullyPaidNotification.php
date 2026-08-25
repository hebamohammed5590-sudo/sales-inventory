<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceFullyPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Invoice $invoice
    ) {}

    public function via(
        object $notifiable
    ): array {
        return [
            'database',
        ];
    }

    public function toArray(
        object $notifiable
    ): array {
        $total = (int) $this->invoice->getRawOriginal(
            'total'
        );

        return [
            'type' => 'invoice_fully_paid',

            'invoice_id' => $this->invoice->id,

            'invoice_number' => $this->invoice->invoice_number,

            'invoice_type' => $this->invoice->type->value,

            'customer_id' => $this->invoice->customer_id,

            'total' => $total,

            'paid_amount' => $this->invoice->paidAmountInCents(),

            'message' => sprintf(
                'Sales invoice "%s" has been fully paid. Total: %s.',

                $this->invoice->invoice_number,

                $this->formatMoney(
                    $total
                )
            ),
        ];
    }

    private function formatMoney(
        int $amount
    ): string {
        return sprintf(
            '%d.%02d',

            intdiv(
                $amount,
                100
            ),

            $amount % 100
        );
    }
}
