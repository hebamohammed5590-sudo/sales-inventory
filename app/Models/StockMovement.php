<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class StockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'source_type',
        'source_id',
        'user_id',
        'type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,

            'quantity_change' => 'integer',

            'quantity_before' => 'integer',

            'quantity_after' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(
                'Stock movements cannot be updated.'
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Stock movements cannot be deleted.'
            );
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
