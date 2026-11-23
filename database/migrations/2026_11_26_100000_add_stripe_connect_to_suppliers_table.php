<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The supplier's Stripe Connect account and what Stripe currently allows it to do.
 * Kept in sync by the `account.updated` webhook.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->unique()->after('commission_bps');
            $table->boolean('stripe_details_submitted')->default(false)->after('stripe_account_id');
            $table->boolean('stripe_transfers_enabled')->default(false)->after('stripe_details_submitted');
            $table->boolean('stripe_payouts_enabled')->default(false)->after('stripe_transfers_enabled');
            $table->timestamp('stripe_synced_at')->nullable()->after('stripe_payouts_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique(['stripe_account_id']);
            $table->dropColumn(['stripe_account_id', 'stripe_details_submitted', 'stripe_transfers_enabled', 'stripe_payouts_enabled', 'stripe_synced_at']);
        });
    }
};
