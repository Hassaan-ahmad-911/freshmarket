<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | approved | suspended

            // Money in cents, commission in basis points (null = platform default).
            $table->unsignedInteger('min_order_amount')->default(0);
            $table->unsignedSmallInteger('commission_bps')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
