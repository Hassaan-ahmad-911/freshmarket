<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Orders\Actions\UpdateSupplierOrderStatus;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Actions\IssueRefund;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** A supplier's view of orders: ONLY their own sub-orders, and only once paid. */
class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = SupplierOrderStatus::tryFrom((string) $request->query('status'));

        $orders = SupplierOrder::query()
            ->whereBelongsTo($request->user()->supplier)
            ->whereHas('order', fn (Builder $q) => $q->whereIn('status', [
                OrderStatus::Paid, OrderStatus::PartiallyRefunded, OrderStatus::Refunded,
            ]))
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->with('order')
            ->withCount('items')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('supplier.orders.index', compact('orders', 'status'));
    }

    public function show(SupplierOrder $supplierOrder): View
    {
        Gate::authorize('view', $supplierOrder);

        return view('supplier.orders.show', [
            'supplierOrder' => $supplierOrder->load(['order.payment', 'items', 'transfer', 'refunds.item']),
        ]);
    }

    /** Refund some units of ONE item — only this supplier's money is touched. */
    public function refundItem(Request $request, SupplierOrder $supplierOrder, OrderItem $item, IssueRefund $issueRefund): RedirectResponse
    {
        Gate::authorize('refund', $supplierOrder);
        abort_unless($item->supplier_order_id === $supplierOrder->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $refund = $issueRefund->item($item, (int) $data['quantity'], $request->user(), $data['reason'] ?? null);

        return back()->with('status', "Refunded {$refund->amount} to the customer ({$refund->description()}).");
    }

    /** Cancel this supplier's part of the order and refund everything left on it. */
    public function cancel(Request $request, SupplierOrder $supplierOrder, IssueRefund $issueRefund): RedirectResponse
    {
        Gate::authorize('refund', $supplierOrder);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $refund = $issueRefund->supplierOrder($supplierOrder, $request->user(), $data['reason'] ?? null);

        return back()->with('status', $refund
            ? "Order cancelled and {$refund->amount} refunded to the customer."
            : 'Order cancelled.');
    }

    public function updateStatus(Request $request, SupplierOrder $supplierOrder, UpdateSupplierOrderStatus $updateStatus): RedirectResponse
    {
        Gate::authorize('update', $supplierOrder);
        $data = $request->validate(['status' => ['required', Rule::enum(SupplierOrderStatus::class)->except([SupplierOrderStatus::Cancelled])]]);

        $updateStatus->handle($supplierOrder, SupplierOrderStatus::from($data['status']));

        return back()->with('status', "Order marked as {$supplierOrder->status->label()}.");
    }
}
