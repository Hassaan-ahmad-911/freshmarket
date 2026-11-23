<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * orders           — what the customer bought and paid for, in ONE payment
 * supplier_orders  — one slice per supplier, with its own status and money split
 * order_items      — the lines, each belonging to one supplier order
 *
 * All money columns are integer cents. Names and prices are COPIED onto the
 * order ("snapshots") so later product edits never change a past order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->nullable()->unique();   // FM-000042
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->index();
            $table->char('currency', 3);

            $table->unsignedInteger('subtotal');        // all items
            $table->unsignedInteger('delivery_total');  // all supplier delivery fees
            $table->unsignedInteger('total');           // what the customer pays
            $table->unsignedInteger('commission_total'); // platform's share

            $table->string('delivery_name');
            $table->string('delivery_phone', 40);
            $table->string('delivery_address');
            $table->string('delivery_area', 100);
            $table->text('delivery_notes')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();     // FM-000042-1
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->index();

            $table->unsignedSmallInteger('commission_bps'); // snapshot of the rate used
            $table->unsignedInteger('subtotal');            // items
            $table->unsignedInteger('delivery_fee');
            $table->unsignedInteger('total');               // subtotal + delivery fee
            $table->unsignedInteger('commission');          // sum of item commissions
            $table->unsignedInteger('payout_amount');       // total - commission → transferred to the supplier

            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('unit', 30)->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->unsignedInteger('commission');      // commission on this line, rounded once
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('supplier_orders');
        Schema::dropIfExists('orders');
    }
};
