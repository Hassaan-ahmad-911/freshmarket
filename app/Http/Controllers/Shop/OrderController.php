<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Orders\Models\Order;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** The customer's order history: the parent order plus each supplier's status. */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->whereBelongsTo($request->user(), 'customer')
            ->with('supplierOrders.supplier')
            ->latest()
            ->paginate(10);

        return view('shop.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('shop.orders.show', [
            'order' => $order->load(['supplierOrders.supplier', 'supplierOrders.items']),
        ]);
    }
}
