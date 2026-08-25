<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $attributes = [
        'quantity' => 0,
    ];

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'cost_price',
        'sell_price',
        'reorder_level',
        'image_path',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => MoneyCast::class,
            'sell_price' => MoneyCast::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            if (blank($product->sku)) {
                $product->sku = static::generateSku();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public static function generateSku(): string
    {
        do {
            $sku = 'PRD-'.Str::upper(Str::random(10));
        } while (
            static::query()
                ->where('sku', $sku)
                ->exists()
        );

        return $sku;
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class
        );
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(
            StockAdjustment::class
        );
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(
            InvoiceItem::class
        );
    }
}
