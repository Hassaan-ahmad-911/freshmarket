<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payments        — the ONE charge on the platform account for an order
 * transfers       — one per supplier order: platform → supplier's connected account
 * webhook_events  — every Stripe event we received, so each is processed exactly once
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);                         // stripe | fake
            $table->string('status', 20)->index();                 // pending | succeeded | failed
            $table->unsignedInteger('amount');
            $table->char('currency', 3);
            $table->string('checkout_session_id')->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->timestamp('checkout_expires_at')->nullable();
            $table->string('payment_intent_id')->nullable()->unique();
            $table->string('charge_id')->nullable();               // needed as source_transaction for transfers
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->index();                 // pending | paid | blocked | failed
            $table->unsignedInteger('amount');
            $table->unsignedInteger('reversed_amount')->default(0);
            $table->char('currency', 3);
            $table->string('destination_account')->nullable();
            $table->string('stripe_transfer_id')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->text('failure_message')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_event_id')->unique();           // the idempotency guard
            $table->string('type')->index();
            $table->string('status', 20)->index();                 // received | processed | ignored | failed
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('payments');
    }
};
