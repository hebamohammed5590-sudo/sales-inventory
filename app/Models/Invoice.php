<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'type',
        'status',
        'customer_id',
        'supplier_id',
        'user_id',
        'invoice_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'notes',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,

            'status' => InvoiceStatus::class,

            'discount_type' => DiscountType::class,

            'invoice_date' => 'date',

            'confirmed_at' => 'datetime',

            'cancelled_at' => 'datetime',

            'subtotal' => MoneyCast::class,

            'discount_value' => MoneyCast::class,

            'discount' => MoneyCast::class,

            'tax' => MoneyCast::class,

            'total' => MoneyCast::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(
            StockMovement::class,
            'source'
        );
    }

    public function isPurchase(): bool
    {
        return $this->type === InvoiceType::Purchase;
    }

    public function isSale(): bool
    {
        return $this->type === InvoiceType::Sale;
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }

    public function isConfirmed(): bool
    {
        return $this->status === InvoiceStatus::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === InvoiceStatus::Cancelled;
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(
            Payment::class,
            'payable'
        );
    }

    public function paidAmountInCents(): int
    {
        if (
            array_key_exists(
                'payments_sum_amount',
                $this->getAttributes()
            )
        ) {
            return (int) $this->getAttribute(
                'payments_sum_amount'
            );
        }

        return (int) $this->payments()
            ->sum('amount');
    }

    public function remainingAmountInCents(): int
    {
        $total = (int) $this->getRawOriginal(
            'total'
        );

        return max(
            0,
            $total - $this->paidAmountInCents()
        );
    }

    public function paidAmount(): string
    {
        return $this->formatMoney(
            $this->paidAmountInCents()
        );
    }

    public function remainingAmount(): string
    {
        return $this->formatMoney(
            $this->remainingAmountInCents()
        );
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
