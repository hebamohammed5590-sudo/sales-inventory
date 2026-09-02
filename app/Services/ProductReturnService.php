<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\ProductReturn;
use App\Models\ProductReturnItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductReturnService
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function create(
        Invoice $invoice,
        User $user,
        array $data
    ): ProductReturn {
        return DB::transaction(function () use (
            $invoice,
            $user,
            $data
        ) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $this->ensureInvoiceCanReceiveReturn(
                $lockedInvoice
            );

            $invoiceItems = $lockedInvoice->items()
                ->with('product')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requestedItems = $data['items'] ?? [];

            if (! is_array($requestedItems)) {
                throw ValidationException::withMessages([
                    'items' => 'Return items must be provided.',
                ]);
            }

            $preparedItems = [];
            $subtotalInCents = 0;

            foreach ($requestedItems as $invoiceItemId => $quantity) {
                if (
                    ! is_numeric($invoiceItemId)
                    || ! is_numeric($quantity)
                    || (int) $quantity != $quantity
                    || (int) $quantity < 0
                ) {
                    throw ValidationException::withMessages([
                        "items.{$invoiceItemId}" => 'Return quantity must be a non-negative whole number.',
                    ]);
                }

                $quantity = (int) $quantity;

                if ($quantity === 0) {
                    continue;
                }

                $invoiceItem = $invoiceItems->get(
                    (int) $invoiceItemId
                );

                if ($invoiceItem === null) {
                    throw ValidationException::withMessages([
                        "items.{$invoiceItemId}" => 'The selected invoice item is invalid.',
                    ]);
                }

                $alreadyReturned = (int) ProductReturnItem::query()
                    ->where(
                        'invoice_item_id',
                        $invoiceItem->id
                    )
                    ->sum('quantity');

                $remainingReturnable = max(
                    0,
                    $invoiceItem->quantity - $alreadyReturned
                );

                if ($quantity > $remainingReturnable) {
                    throw ValidationException::withMessages([
                        "items.{$invoiceItemId}" => sprintf(
                            'Only %d unit(s) remain available to return.',
                            $remainingReturnable
                        ),
                    ]);
                }

                $unitPriceInCents = (int) $invoiceItem
                    ->getRawOriginal('unit_price');

                $lineTotalInCents = $unitPriceInCents * $quantity;

                $subtotalInCents += $lineTotalInCents;

                $preparedItems[] = [
                    'invoice_item' => $invoiceItem,
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceInCents,
                    'line_total' => $lineTotalInCents,
                ];
            }

            if ($preparedItems === []) {
                throw ValidationException::withMessages([
                    'items' => 'At least one product must be returned.',
                ]);
            }

            $returnDate = isset($data['return_date'])
                ? Carbon::parse($data['return_date'])->toDateString()
                : now()->toDateString();

            $productReturn = ProductReturn::create([
                'return_number' => null,
                'invoice_id' => $lockedInvoice->id,
                'user_id' => $user->id,
                'return_date' => $returnDate,
                'subtotal' => $this->formatMoney(
                    $subtotalInCents
                ),
                'reason' => $data['reason'] ?? null,
            ]);

            $returnNumber = sprintf(
                'RET-%s-%06d',
                Carbon::parse($returnDate)->format('Y'),
                $productReturn->id
            );

            $productReturn->update([
                'return_number' => $returnNumber,
            ]);

            $activityItems = [];

            foreach ($preparedItems as $preparedItem) {
                $invoiceItem = $preparedItem['invoice_item'];

                $returnItem = $productReturn->items()->create([
                    'invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'quantity' => $preparedItem['quantity'],
                    'unit_price' => $this->formatMoney(
                        $preparedItem['unit_price']
                    ),
                    'line_total' => $this->formatMoney(
                        $preparedItem['line_total']
                    ),
                ]);

                $this->stockService->apply(
                    product: $invoiceItem->product,
                    source: $productReturn,
                    user: $user,
                    type: StockMovementType::Return,
                    quantityChange: $preparedItem['quantity'],
                    notes: sprintf(
                        'Product return %s for invoice %s.',
                        $returnNumber,
                        $lockedInvoice->invoice_number
                    )
                );

                $activityItems[] = [
                    'invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'product_name' => $invoiceItem->product->name,
                    'quantity' => $returnItem->quantity,
                    'line_total' => $preparedItem['line_total'],
                ];
            }

            $this->activityLogService->record(
                actor: $user,
                action: 'product_return.created',
                subject: $productReturn,
                description: sprintf(
                    '%s created product return %s for invoice %s.',
                    $user->name,
                    $returnNumber,
                    $lockedInvoice->invoice_number
                ),
                properties: [
                    'product_return_id' => $productReturn->id,
                    'return_number' => $returnNumber,
                    'invoice_id' => $lockedInvoice->id,
                    'invoice_number' => $lockedInvoice->invoice_number,
                    'subtotal' => $subtotalInCents,
                    'items' => $activityItems,
                ]
            );

            return $productReturn->refresh()->load([
                'invoice.customer',
                'user',
                'items.product',
                'items.invoiceItem',
                'stockMovements',
            ]);
        }, 3);
    }

    private function ensureInvoiceCanReceiveReturn(
        Invoice $invoice
    ): void {
        if (! $invoice->isSale()) {
            throw ValidationException::withMessages([
                'invoice' => 'Only sales invoices can receive product returns.',
            ]);
        }

        if (
            ! in_array(
                $invoice->status,
                [
                    InvoiceStatus::Confirmed,
                    InvoiceStatus::PartiallyPaid,
                    InvoiceStatus::Paid,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'invoice' => sprintf(
                    'An invoice with status %s cannot receive product returns.',
                    $invoice->status->value
                ),
            ]);
        }
    }

    private function formatMoney(
        int $amount
    ): string {
        return sprintf(
            '%d.%02d',
            intdiv($amount, 100),
            $amount % 100
        );
    }
}
