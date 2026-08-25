<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('sku')->unique();

            $table->string('name');

            $table->text('description')->nullable();

            $table->unsignedBigInteger('cost_price');

            $table->unsignedBigInteger('sell_price');

            $table->unsignedInteger('quantity')->default(0);

            $table->unsignedInteger('reorder_level')->default(5);

            $table->string('image_path')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
