<?php

use App\Domain\Catalog\Models\Product;

/*
| Commission is taken on goods only (not delivery), per line, rounded half-up
| once per line, using each supplier's own rate (snapshotted on the order).
*/

beforeEach(function () {
    config(['freshmarket.default_commission_bps' => 1000, 'freshmarket.default_delivery_fee' => 499, 'freshmarket.free_delivery_threshold' => 0]);
});

it('uses each supplier\'s own commission rate', function () {
    $standard = camdenStore();          // platform default 10%
    $negotiated = camdenStore(1200);    // 12%
    $a = Product::factory()->for($standard)->create(['price' => 1000]);
    $b = Product::factory()->for($negotiated)->create(['price' => 1000]);

    $order = placeOrder([$a->id => 1, $b->id => 1]);
    $soA = $order->supplierOrders->firstWhere('supplier_id', $standard->id);
    $soB = $order->supplierOrders->firstWhere('supplier_id', $negotiated->id);

    expect($soA->commission_bps)->toBe(1000)->and($soA->commission->amount)->toBe(100)
        ->and($soB->commission_bps)->toBe(1200)->and($soB->commission->amount)->toBe(120)
        ->and($order->commission_total->amount)->toBe(220);
});

it('pays the supplier the total minus commission, delivery fee included', function () {
    $store = camdenStore(1000);
    $p = Product::factory()->for($store)->create(['price' => 2500]);

    $so = placeOrder([$p->id => 2])->supplierOrders->sole();

    // 50.00 goods + 4.99 delivery = 54.99; commission 10% of 50.00 = 5.00; payout 49.99
    expect($so->total->amount)->toBe(5499)
        ->and($so->commission->amount)->toBe(500)
        ->and($so->payout_amount->amount)->toBe(4999);
});

it('rounds commission per line, and the order adds up exactly', function () {
    $store = camdenStore(1250); // 12.5%
    $p1 = Product::factory()->for($store)->create(['price' => 333]);  // line 999 → 124.875 → 125
    $p2 = Product::factory()->for($store)->create(['price' => 101]);  // line 101 → 12.625 → 13

    $so = placeOrder([$p1->id => 3, $p2->id => 1])->supplierOrders->sole();

    expect($so->items->pluck('commission.amount')->sort()->values()->all())->toBe([13, 125])
        ->and($so->commission->amount)->toBe(138)
        ->and($so->payout_amount->amount + $so->commission->amount)->toBe($so->total->amount);
});

it('snapshots the rate so later changes do not affect existing orders', function () {
    $store = camdenStore(1000);
    $p = Product::factory()->for($store)->create(['price' => 1000]);
    $so = placeOrder([$p->id => 1])->supplierOrders->sole();

    $store->update(['commission_bps' => 2500]);

    expect($so->refresh()->commission_bps)->toBe(1000)->and($so->commission->amount)->toBe(100);
});
