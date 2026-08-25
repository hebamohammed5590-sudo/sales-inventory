<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\InvoiceSequence;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function generate(
        InvoiceType $type,
        ?int $year = null
    ): string {
        $year ??= now()->year;

        $this->ensureSequenceExists(
            $type,
            $year
        );

        return DB::transaction(function () use (
            $type,
            $year
        ) {
            $sequence = InvoiceSequence::query()
                ->where('type', $type->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            $prefix = $this->resolvePrefix($type);

            return sprintf(
                '%s-%d-%06d',
                $prefix,
                $year,
                $sequence->last_number
            );
        }, 3);
    }

    private function ensureSequenceExists(
        InvoiceType $type,
        int $year
    ): void {
        try {
            InvoiceSequence::query()->firstOrCreate(
                [
                    'type' => $type->value,
                    'year' => $year,
                ],
                [
                    'last_number' => 0,
                ]
            );
        } catch (QueryException $exception) {
            $sequenceExists = InvoiceSequence::query()
                ->where('type', $type->value)
                ->where('year', $year)
                ->exists();

            if (! $sequenceExists) {
                throw $exception;
            }
        }
    }

    private function resolvePrefix(
        InvoiceType $type
    ): string {
        return match ($type) {
            InvoiceType::Sale => Setting::get(
                'invoice_prefix',
                'INV'
            ),

            InvoiceType::Purchase => Setting::get(
                'purchase_invoice_prefix',
                'PUR'
            ),
        };
    }
}
