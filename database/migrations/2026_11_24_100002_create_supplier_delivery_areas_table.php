<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Areas (towns, districts or postcodes) a supplier delivers to.
 * A separate table instead of a JSON column, so "who delivers to X?"
 * is a fast, indexed query.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('normalized', 100)->index(); // lower-case, trimmed: used for matching
            $table->timestamps();

            $table->unique(['supplier_id', 'normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_delivery_areas');
    }
};
