<?php

use App\Domain\Orders\Actions\ReleaseUnpaidOrder;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Checkout reserves stock straight away. Orders that stay unpaid longer than
| the Stripe Checkout session lifetime (30 min) are cancelled and restocked.
*/
Artisan::command('orders:release-unpaid {--minutes=45}', function (ReleaseUnpaidOrder $release) {
    $orders = Order::where('status', OrderStatus::PendingPayment)
        ->where('created_at', '<', now()->subMinutes((int) $this->option('minutes')))
        ->get();

    $orders->each(fn (Order $order) => $release->handle($order));

    $this->info("Released {$orders->count()} unpaid order(s).");
})->purpose('Cancel abandoned unpaid orders and put their stock back');

Schedule::command('orders:release-unpaid')->everyFiveMinutes();

/*
| Demo helper: the Stripe Dashboard (test mode) pages that prove an order's money flow.
*/
Artisan::command('orders:stripe-links {number : e.g. FM-000001}', function (string $number) {
    $order = Order::where('number', $number)->with(['payment', 'refunds', 'supplierOrders.supplier', 'supplierOrders.transfer'])->firstOrFail();
    $dashboard = 'https://dashboard.stripe.com/test';

    $this->info("Order {$order->number} — {$order->status->label()} — paid {$order->total}, refunded {$order->refunded_total}");
    $this->line("  Customer payment (one charge):  {$dashboard}/payments/{$order->payment?->payment_intent_id}");

    foreach ($order->supplierOrders as $so) {
        $this->line("  {$so->supplier->name}:");
        $this->line("    Connected account (balance):  {$dashboard}/connect/accounts/{$so->supplier->stripe_account_id}");
        $this->line("    Transfer {$so->transfer?->amount} (reversed {$so->transfer?->reversed_amount}):  {$so->transfer?->stripe_transfer_id}  ← paste into the Dashboard search bar");
    }

    foreach ($order->refunds as $refund) {
        $this->line("  Refund {$refund->amount} ({$refund->description()}): {$refund->stripe_refund_id} · reversal {$refund->stripe_reversal_id}");
    }
})->purpose('Print the Stripe Dashboard links for an order (for demos)');
