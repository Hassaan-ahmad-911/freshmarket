<?php

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Events\ProductStockLow;
use App\Domain\Catalog\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('decreases stock', function () {
    $product = Product::factory()->create(['stock' => 10]);

    app(AdjustStock::class)->decrease($product, 3);

    expect($product->refresh()->stock)->toBe(7);
});

it('never lets stock go below zero', function () {
    $product = Product::factory()->create(['stock' => 2]);

    expect(fn () => app(AdjustStock::class)->decrease($product, 3))->toThrow(InsufficientStockException::class);
    expect($product->refresh()->stock)->toBe(2);
});

it('fires the low-stock event only when the threshold is crossed', function () {
    Event::fake([ProductStockLow::class]);
    $product = Product::factory()->create(['stock' => 7, 'low_stock_threshold' => 5]);
    $adjust = app(AdjustStock::class);

    $adjust->decrease($product, 1);   // 7 → 6: still above
    Event::assertNotDispatched(ProductStockLow::class);

    $adjust->decrease($product, 2);   // 6 → 4: crossed!
    Event::assertDispatchedTimes(ProductStockLow::class, 1);

    $adjust->decrease($product, 1);   // 4 → 3: already low, no repeat alert
    Event::assertDispatchedTimes(ProductStockLow::class, 1);
});

it('emails the store owner a low-stock alert', function () {
    Notification::fake();
    $product = Product::factory()->create(['stock' => 6, 'low_stock_threshold' => 5]);

    app(AdjustStock::class)->decrease($product, 2);

    Notification::assertSentTo($product->supplier->owner, LowStockAlert::class);
});

it('puts stock back', function () {
    $product = Product::factory()->create(['stock' => 0]);

    app(AdjustStock::class)->increase($product, 4);

    expect($product->refresh()->stock)->toBe(4);
});

it('shows low-stock products on the supplier dashboard', function () {
    $product = Product::factory()->create(['name' => 'Almost Gone Apples', 'stock' => 2, 'low_stock_threshold' => 5]);

    $this->actingAs(supplierOwner($product->supplier))
        ->get(route('supplier.dashboard'))
        ->assertSee('Almost Gone Apples')
        ->assertSee('2 left');
});
