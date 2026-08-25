<?php

namespace App\Models;

use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Model;

class InvoiceSequence extends Model
{
    protected $fillable = [
        'type',
        'year',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,

            'year' => 'integer',

            'last_number' => 'integer',
        ];
    }
}
