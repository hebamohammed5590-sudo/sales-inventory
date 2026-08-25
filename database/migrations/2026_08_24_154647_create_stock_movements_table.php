<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->morphs('source');

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('type');

            $table->integer('quantity_change');

            $table->unsignedInteger('quantity_before');

            $table->unsignedInteger('quantity_after');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
