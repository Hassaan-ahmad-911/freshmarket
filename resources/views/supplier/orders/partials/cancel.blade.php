@if ($supplierOrder->order->status->isPaid() && $supplierOrder->status->isCancellable() && auth()->user()->can('refund', $supplierOrder))
    <form method="POST" action="{{ route('supplier.orders.cancel', $supplierOrder) }}"
          x-data @submit.prevent="if (confirm('Cancel your part of this order and refund {{ $supplierOrder->refundableAmount() }} to the customer? Other suppliers in the order are not affected.')) $el.submit()"
          class="border-t border-gray-100 pt-3">
        @csrf
        <input type="text" name="reason" placeholder="Reason (optional)" maxlength="255" class="w-full rounded-md border-gray-300 text-sm">
        <button class="mt-2 rounded-md border border-red-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 hover:bg-red-50">
            Cancel &amp; refund {{ $supplierOrder->refundableAmount() }}
        </button>
    </form>
@endif
