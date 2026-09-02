<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_return_id',
        'invoice_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,

            'line_total' => MoneyCast::class,
        ];
    }

    public function productReturn(): BelongsTo
    {
        return $this->belongsTo(
            ProductReturn::class
        );
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(
            InvoiceItem::class
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }
}
