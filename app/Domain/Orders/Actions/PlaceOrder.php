<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Catalog\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\DeliveryDetails;
use App\Domain\Orders\Services\OrderPricing;
use App\Domain\Shared\Money\Money;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a list of (product, quantity) lines into:
 *
 *   1 Order (parent)  ──┬── SupplierOrder for supplier A ── its items
 *                       └── SupplierOrder for supplier B ── its items
 *
 * Everything happens in ONE database transaction: stock is reserved, prices are
 * copied from the database (never trusted from the browser), commission and
 * delivery are calculated. If anything fails, nothing is saved and no stock moves.
 *
 * The order starts as "pending_payment". It only becomes "paid" when Stripe's
 * webhook confirms the payment (Phase 4).
 */
class PlaceOrder
{
    public function __construct(
        private OrderPricing $pricing,
        private AdjustStock $stock,
    ) {}

    /**
     * @param  array<int, int>  $lines  product_id => quantity
     */
    public function handle(User $customer, array $lines, DeliveryDetails $delivery): Order
    {
        $lines = array_filter($lines, fn (int $quantity) => $quantity > 0);

        if ($lines === []) {
            throw ValidationException::withMessages(['cart' => 'Your basket is empty.']);
        }

        return DB::transaction(function () use ($customer, $lines, $delivery) {
            // lockForUpdate: on MySQL, other checkouts wait for us before touching these rows.
            $products = Product::query()
                ->whereKey(array_keys($lines))
                ->with('supplier.deliveryAreas')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->assertAllAvailable($products, $lines);

            $bySupplier = $products->groupBy('supplier_id');
            $this->assertSupplierRules($bySupplier, $lines, $delivery);

            $order = Order::create([
                'user_id' => $customer->id,
                'status' => OrderStatus::PendingPayment,
                'currency' => config('freshmarket.currency'),
                'subtotal' => 0, 'delivery_total' => 0, 'total' => 0, 'commission_total' => 0,
                ...$delivery->toOrderAttributes(),
            ]);
            $order->update(['number' => 'FM-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);

            $position = 0;
            foreach ($bySupplier as $supplierProducts) {
                $this->createSupplierOrder($order, $supplierProducts, $lines, ++$position);
            }

            $this->refreshTotals($order);

            return $order->load('supplierOrders.items');
        });
    }

    /** @param  Collection<int, Product>  $products */
    private function createSupplierOrder(Order $order, Collection $products, array $lines, int $position): void
    {
        $supplier = $products->first()->supplier;
        $bps = $supplier->commissionBps();   // snapshot: later rate changes don't touch this order

        $supplierOrder = $order->supplierOrders()->create([
            'number' => "{$order->number}-{$position}",
            'supplier_id' => $supplier->id,
            'status' => SupplierOrderStatus::Pending,
            'commission_bps' => $bps,
            'subtotal' => 0, 'delivery_fee' => 0, 'total' => 0, 'commission' => 0, 'payout_amount' => 0,
        ]);

        $subtotal = Money::zero();
        $commission = Money::zero();

        foreach ($products as $product) {
            $quantity = $lines[$product->id];
            $lineTotal = $product->price->multiply($quantity);
            $lineCommission = $this->pricing->lineCommission($lineTotal, $bps);

            $this->reserveStock($product, $quantity);

            $supplierOrder->items()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit' => $product->unit,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'line_total' => $lineTotal,
                'commission' => $lineCommission,
            ]);

            $subtotal = $subtotal->add($lineTotal);
            $commission = $commission->add($lineCommission);
        }

        $deliveryFee = $this->pricing->deliveryFee($subtotal);
        $total = $subtotal->add($deliveryFee);

        $supplierOrder->update([
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $total,
            'commission' => $commission,
            'payout_amount' => $total->subtract($commission),
        ]);
    }

    private function refreshTotals(Order $order): void
    {
        // toBase(): plain query (no model casts), so the sums come back as numbers.
        $sums = $order->supplierOrders()->toBase()
            ->selectRaw('sum(subtotal) as subtotal, sum(delivery_fee) as delivery, sum(total) as total, sum(commission) as commission')
            ->first();

        $order->update([
            'subtotal' => (int) $sums->subtotal,
            'delivery_total' => (int) $sums->delivery,
            'total' => (int) $sums->total,
            'commission_total' => (int) $sums->commission,
        ]);
    }

    private function reserveStock(Product $product, int $quantity): void
    {
        try {
            $this->stock->decrease($product, $quantity);
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['cart' => $e->getMessage()]);
        }
    }

    /** @param  Collection<int, Product>  $products */
    private function assertAllAvailable(Collection $products, array $lines): void
    {
        foreach (array_keys($lines) as $productId) {
            $product = $products->get($productId);

            if (! $product || ! $product->isVisible()) {
                throw ValidationException::withMessages(['cart' => 'A product in your basket is no longer available. Please review your basket.']);
            }
        }
    }

    /** @param  Collection<int, Collection<int, Product>>  $bySupplier */
    private function assertSupplierRules(Collection $bySupplier, array $lines, DeliveryDetails $delivery): void
    {
        $errors = [];

        foreach ($bySupplier as $products) {
            $supplier = $products->first()->supplier;
            $subtotal = Money::sum($products->map(fn (Product $p) => $p->price->multiply($lines[$p->id])));

            if ($subtotal->lessThan($supplier->min_order_amount)) {
                $errors[] = "{$supplier->name} has a minimum order of {$supplier->min_order_amount}.";
            }

            if ($supplier->deliveryAreas->isNotEmpty() && ! $supplier->deliversTo($delivery->area)) {
                $errors[] = "{$supplier->name} does not deliver to {$delivery->area}.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['cart' => $errors]);
        }
    }
}
