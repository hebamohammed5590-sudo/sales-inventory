<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_number',
        'invoice_id',
        'user_id',
        'return_date',
        'subtotal',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',

            'subtotal' => MoneyCast::class,
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(
            Invoice::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            ProductReturnItem::class
        );
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(
            StockMovement::class,
            'source'
        );
    }
}
