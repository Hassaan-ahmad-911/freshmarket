<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partial refunds.
 *
 * A refund of one item (or a whole supplier order) is split three ways:
 *   amount            → back to the customer's card            (Stripe Refund)
 *   commission_amount → the platform gives up its commission on it
 *   reversal_amount   → pulled back from THAT supplier only     (Stripe Transfer Reversal)
 *   amount = commission_amount + reversal_amount
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete(); // null = whole sub-order
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 20);                       // item | supplier_order
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('commission_amount');
            $table->unsignedInteger('reversal_amount')->default(0);
            $table->unsignedInteger('delivery_amount')->default(0); // delivery fee included (whole sub-order cancels)
            $table->json('lines');                                  // [{item_id, quantity, amount, commission}]
            $table->string('reason')->nullable();

            $table->string('status', 20)->index();            // pending | succeeded | failed
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->string('reversal_status', 20)->nullable(); // succeeded | not_needed | failed
            $table->string('stripe_reversal_id')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('refunded_quantity')->default(0);
            $table->unsignedInteger('refunded_amount')->default(0);
            $table->unsignedInteger('refunded_commission')->default(0);
        });

        Schema::table('supplier_orders', function (Blueprint $table) {
            $table->unsignedInteger('refunded_amount')->default(0);
            $table->unsignedInteger('refunded_commission')->default(0);
            $table->boolean('delivery_refunded')->default(false);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('refunded_total')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('refunded_total'));
        Schema::table('supplier_orders', fn (Blueprint $table) => $table->dropColumn(['refunded_amount', 'refunded_commission', 'delivery_refunded']));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['refunded_quantity', 'refunded_amount', 'refunded_commission']));
        Schema::dropIfExists('refunds');
    }
};
