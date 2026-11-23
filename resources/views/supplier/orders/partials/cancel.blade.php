@if ($supplierOrder->order->status->isPaid() && $supplierOrder->status->isCancellable() && auth()->user()->can('refund', $supplierOrder))
    <form method="POST" action="{{ route('supplier.orders.cancel', $supplierOrder) }}"
          x-data @submit.prevent="if (confirm('Cancel your part of this order and refund {{ $supplierOrder->refundableAmount() }} to the customer? Other suppliers in the order are not affected.')) $el.submit()"
          class="border-t border-stone-100 pt-4">
        @csrf
        <input type="text" name="reason" placeholder="Reason (optional)" maxlength="255" class="input">
        <button class="mt-2 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-50">
            <x-icon name="x" class="h-4 w-4" /> Cancel &amp; refund {{ $supplierOrder->refundableAmount() }}
        </button>
    </form>
@endif
