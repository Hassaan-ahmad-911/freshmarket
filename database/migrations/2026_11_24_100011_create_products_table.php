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
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');                 // cents
            $table->string('unit', 30)->nullable();           // "kg", "each", "500 g"...
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // Soft delete: orders placed earlier still point at this product.
            $table->softDeletes();

            $table->index(['supplier_id', 'is_active']);
            $table->index(['is_active', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
