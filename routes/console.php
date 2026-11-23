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
