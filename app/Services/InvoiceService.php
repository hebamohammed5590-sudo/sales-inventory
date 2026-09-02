<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\StockMovementType;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceNumberService $invoiceNumberService,
        private readonly StockService $stockService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function create(
        InvoiceType $type,
        User $user,
        array $data
    ): Invoice {
        return DB::transaction(function () use (
            $type,
            $user,
            $data
        ) {
            $this->validateParty(
                $type,
                $data
            );

            $items = $this->prepareItems(
                $type,
                $data['items'] ?? []
            );

            $subtotal = array_sum(
                array_column(
                    $items,
                    'line_total'
                )
            );

            $discountType = $this->resolveDiscountType(
                $data['discount_type'] ?? null
            );

            $discountValue = $this->parseMoney(
                $data['discount_value'] ?? '0'
            );

            $discount = $this->calculateDiscount(
                $subtotal,
                $discountType,
                $discountValue
            );

            $taxableAmount = $subtotal - $discount;

            $tax = $this->calculateTax(
                $taxableAmount
            );

            $total = $taxableAmount + $tax;

            $invoiceDate = Carbon::parse(
                $data['invoice_date'] ?? now()
            );

            $invoice = Invoice::query()->create([
                'invoice_number' => $this->invoiceNumberService->generate(
                    $type,
                    $invoiceDate->year
                ),

                'type' => $type,

                'status' => InvoiceStatus::Draft,

                'customer_id' => $type === InvoiceType::Sale
                    ? $data['customer_id']
                    : null,

                'supplier_id' => $type === InvoiceType::Purchase
                    ? $data['supplier_id']
                    : null,

                'user_id' => $user->id,

                'invoice_date' => $invoiceDate,

                'subtotal' => $this->formatMoney($subtotal),

                'discount_type' => $discountType,

                'discount_value' => $this->formatMoney(
                    $discountValue
                ),

                'discount' => $this->formatMoney($discount),

                'tax' => $this->formatMoney($tax),

                'total' => $this->formatMoney($total),

                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'unit_price' => $this->formatMoney(
                        $item['unit_price']
                    ),

                    'line_total' => $this->formatMoney(
                        $item['line_total']
                    ),
                ]);
            }

            $this->activityLogService->record(
                actor: $user,
                action: 'invoice.created',
                subject: $invoice,
                description: sprintf(
                    '%s created invoice %s.',
                    $user->name,
                    $invoice->invoice_number
                ),
                properties: [
                    'invoice_type' => $invoice->type->value,
                    'status' => $invoice->status->value,
                    'total_in_cents' => (int) $invoice->getRawOriginal('total'),
                ]
            );

            return $invoice->load([
                'items.product',
                'customer',
                'supplier',
                'user',
            ]);
        }, 3);
    }

    public function confirm(
        Invoice $invoice,
        User $user
    ): Invoice {
        return DB::transaction(function () use (
            $invoice,
            $user
        ) {
            $lockedInvoice = Invoice::query()
                ->with('items.product')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $this->ensureTransitionAllowed(
                $lockedInvoice,
                InvoiceStatus::Confirmed
            );

            foreach ($lockedInvoice->items as $item) {
                $movementType = $lockedInvoice->isPurchase()
                    ? StockMovementType::Purchase
                    : StockMovementType::Sale;

                $quantityChange = $lockedInvoice->isPurchase()
                    ? $item->quantity
                    : -$item->quantity;

                $this->stockService->apply(
                    product: $item->product,

                    source: $lockedInvoice,

                    user: $user,

                    type: $movementType,

                    quantityChange: $quantityChange,

                    notes: sprintf(
                        'Invoice %s confirmed.',
                        $lockedInvoice->invoice_number
                    )
                );
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $this->activityLogService->record(
                actor: $user,
                action: 'invoice.confirmed',
                subject: $lockedInvoice,
                description: sprintf(
                    '%s confirmed invoice %s.',
                    $user->name,
                    $lockedInvoice->invoice_number
                ),
                properties: [
                    'previous_status' => InvoiceStatus::Draft->value,
                    'new_status' => InvoiceStatus::Confirmed->value,
                ]
            );

            return $lockedInvoice->refresh()->load([
                'items.product',
                'stockMovements',
            ]);
        }, 3);
    }

    public function cancel(
        Invoice $invoice,
        User $user
    ): Invoice {
        return DB::transaction(function () use (
            $invoice,
            $user
        ) {
            $lockedInvoice = Invoice::query()
                ->with('items.product')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $this->ensureTransitionAllowed(
                $lockedInvoice,
                InvoiceStatus::Cancelled
            );

            if (
                $lockedInvoice->isSale()
                && $lockedInvoice->productReturns()->exists()
            ) {
                throw ValidationException::withMessages([
                    'invoice' => 'A sales invoice with product returns cannot be cancelled.',
                ]);
            }

            if (! $lockedInvoice->isDraft()) {
                foreach ($lockedInvoice->items as $item) {
                    $movementType = $lockedInvoice->isSale()
                        ? StockMovementType::Return
                        : StockMovementType::Adjustment;

                    $quantityChange = $lockedInvoice->isSale()
                        ? $item->quantity
                        : -$item->quantity;

                    $currentStock = $item->product->fresh()->quantity;

                    if (
                        $lockedInvoice->isPurchase()
                        && $currentStock < $item->quantity
                    ) {
                        throw ValidationException::withMessages([
                            'stock' => 'Cannot cancel purchase invoice as product stock would become negative.',
                        ]);
                    }

                    $this->stockService->apply(
                        product: $item->product,
                        source: $lockedInvoice,
                        user: $user,
                        type: $movementType,
                        quantityChange: $quantityChange,
                        notes: sprintf(
                            'Invoice %s cancelled.',
                            $lockedInvoice->invoice_number
                        )
                    );
                }
            }

            $lockedInvoice->update([
                'status' => InvoiceStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $this->activityLogService->record(
                actor: $user,
                action: 'invoice.cancelled',
                subject: $lockedInvoice,
                description: sprintf(
                    '%s cancelled invoice %s.',
                    $user->name,
                    $lockedInvoice->invoice_number
                ),
                properties: []
            );

            return $lockedInvoice->refresh()->load([
                'items.product',
                'stockMovements',
            ]);
        }, 3);
    }

    private function validateParty(
        InvoiceType $type,
        array $data
    ): void {
        if (
            $type === InvoiceType::Sale
            && empty($data['customer_id'])
        ) {
            throw ValidationException::withMessages([
                'customer_id' => 'A sales invoice requires a customer.',
            ]);
        }

        if (
            $type === InvoiceType::Purchase
            && empty($data['supplier_id'])
        ) {
            throw ValidationException::withMessages([
                'supplier_id' => 'A purchase invoice requires a supplier.',
            ]);
        }
    }

    private function prepareItems(
        InvoiceType $type,
        array $items
    ): array {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'An invoice must contain at least one item.',
            ]);
        }

        $preparedItems = [];
        $productIds = [];

        foreach ($items as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? null;

            if (
                ! is_numeric($quantity)
                || (int) $quantity != $quantity
                || (int) $quantity < 1
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'Quantity must be a positive whole number.',
                ]);
            }

            if (in_array(
                $productId,
                $productIds,
                true
            )) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'The same product cannot appear twice.',
                ]);
            }

            $product = Product::query()->findOrFail(
                $productId
            );

            $unitPrice = isset($item['unit_price'])
    && $item['unit_price'] !== ''
    ? $this->parseMoney($item['unit_price'])
    : (int) $product->getRawOriginal(
        $type === InvoiceType::Purchase
            ? 'cost_price'
            : 'sell_price'
    );

            if ($unitPrice <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_price" => 'Unit price must be greater than zero.',
                ]);
            }
            $quantity = (int) $quantity;

            $preparedItems[] = [
                'product_id' => $product->id,

                'quantity' => $quantity,

                'unit_price' => $unitPrice,

                'line_total' => $unitPrice * $quantity,
            ];

            $productIds[] = $productId;
        }

        return $preparedItems;
    }

    private function resolveDiscountType(
        mixed $value
    ): ?DiscountType {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DiscountType) {
            return $value;
        }

        $discountType = DiscountType::tryFrom(
            (string) $value
        );

        if ($discountType === null) {
            throw ValidationException::withMessages([
                'discount_type' => 'The discount type is invalid.',
            ]);
        }

        return $discountType;
    }

    private function calculateDiscount(
        int $subtotal,
        ?DiscountType $discountType,
        int $discountValue
    ): int {
        if ($discountType === null) {
            if ($discountValue !== 0) {
                throw ValidationException::withMessages([
                    'discount_type' => 'Select a discount type.',
                ]);
            }

            return 0;
        }

        if ($discountType === DiscountType::Fixed) {
            if ($discountValue > $subtotal) {
                throw ValidationException::withMessages([
                    'discount_value' => 'The discount cannot exceed the subtotal.',
                ]);
            }

            return $discountValue;
        }

        if ($discountValue > 10000) {
            throw ValidationException::withMessages([
                'discount_value' => 'The percentage discount cannot exceed 100%.',
            ]);
        }

        return intdiv(
            ($subtotal * $discountValue) + 5000,
            10000
        );
    }

    private function calculateTax(
        int $amount
    ): int {
        $taxRate = $this->parseMoney(
            Setting::get(
                'tax_rate',
                '0'
            )
        );

        return intdiv(
            ($amount * $taxRate) + 5000,
            10000
        );
    }

    private function ensureTransitionAllowed(
        Invoice $invoice,
        InvoiceStatus $newStatus
    ): void {
        if (
            ! $invoice->status->canTransitionTo(
                $newStatus
            )
        ) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Cannot change invoice status from %s to %s.',
                    $invoice->status->value,
                    $newStatus->value
                ),
            ]);
        }
    }

    private function parseMoney(
        mixed $value
    ): int {
        $value = trim((string) $value);

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $value
            )
        ) {
            throw ValidationException::withMessages([
                'amount' => 'The amount must be a valid non-negative number.',
            ]);
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        $fraction = str_pad(
            $fraction,
            2,
            '0'
        );

        return ((int) $whole * 100) + (int) $fraction;
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
