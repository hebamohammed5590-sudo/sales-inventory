<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_return_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('invoice_item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->unsignedBigInteger('unit_price');

            $table->unsignedBigInteger('line_total');

            $table->timestamps();

            $table->unique([
                'product_return_id',
                'invoice_item_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_return_items');
    }
};
