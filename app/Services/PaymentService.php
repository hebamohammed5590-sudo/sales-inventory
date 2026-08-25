<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function create(
        Invoice $invoice,
        User $user,
        array $data
    ): Payment {
        return DB::transaction(function () use (
            $invoice,
            $user,
            $data
        ) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $this->ensureInvoiceCanReceivePayment(
                $lockedInvoice
            );

            $amount = $this->parseMoney(
                $data['amount'] ?? null
            );

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment amount must be greater than zero.',
                ]);
            }

            $remaining = $lockedInvoice->remainingAmountInCents();

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'The payment amount cannot exceed the remaining balance of %s.',
                        $this->formatMoney($remaining)
                    ),
                ]);
            }

            $method = $this->resolvePaymentMethod(
                $data['method'] ?? null
            );

            $payment = $lockedInvoice->payments()->create([
                'user_id' => $user->id,

                'amount' => $this->formatMoney($amount),

                'method' => $method,

                'reference' => $data['reference'] ?? null,

                'paid_at' => isset($data['paid_at'])
                    ? Carbon::parse($data['paid_at'])
                    : now(),

                'notes' => $data['notes'] ?? null,
            ]);

            return $payment->load([
                'payable',
                'user',
            ]);
        }, 3);
    }

    private function ensureInvoiceCanReceivePayment(
        Invoice $invoice
    ): void {
        if (
            ! in_array(
                $invoice->status,
                [
                    InvoiceStatus::Confirmed,
                    InvoiceStatus::PartiallyPaid,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'invoice' => sprintf(
                    'An invoice with status %s cannot receive payments.',
                    $invoice->status->value
                ),
            ]);
        }
    }

    private function resolvePaymentMethod(
        mixed $value
    ): PaymentMethod {
        if ($value instanceof PaymentMethod) {
            return $value;
        }

        $method = PaymentMethod::tryFrom(
            (string) $value
        );

        if ($method === null) {
            throw ValidationException::withMessages([
                'method' => 'The selected payment method is invalid.',
            ]);
        }

        return $method;
    }

    private function parseMoney(
        mixed $value
    ): int {
        if (
            $value === null
            || $value === ''
        ) {
            throw ValidationException::withMessages([
                'amount' => 'The payment amount is required.',
            ]);
        }

        $value = trim(
            (string) $value
        );

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw ValidationException::withMessages([
                'amount' => 'The payment amount must be a valid non-negative number with no more than two decimal places.',
            ]);
        }

        [$whole, $fraction] = array_pad(
            explode(
                '.',
                $value,
                2
            ),
            2,
            '0'
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) $fraction;
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
