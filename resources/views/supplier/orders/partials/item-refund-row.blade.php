{{-- Under each item: what was refunded, and a small form to refund more units. --}}
@php($canRefund = $supplierOrder->order->status->isPaid()
    && $supplierOrder->status !== \App\Domain\Orders\Enums\SupplierOrderStatus::Cancelled
    && auth()->user()->can('refund', $supplierOrder))
@if ($item->refunded_quantity > 0 || ($canRefund && $item->refundableQuantity() > 0))
    <tr class="bg-stone-50/60">
        <td colspan="4" class="px-5 pb-3 pt-0">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="text-sky-700">
                    @if ($item->refunded_quantity > 0)
                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 font-medium ring-1 ring-sky-200"><x-icon name="refund" class="h-3.5 w-3.5" /> Refunded {{ $item->refunded_quantity }} of {{ $item->quantity }} (− {{ $item->refunded_amount }})</span>
                    @endif
                </span>
                @if ($canRefund && $item->refundableQuantity() > 0)
                    <form method="POST" action="{{ route('supplier.orders.items.refund', [$supplierOrder, $item]) }}"
                          x-data @submit.prevent="if (confirm('Refund the selected quantity to the customer? This sends money back to their card.')) $el.submit()"
                          class="flex items-center gap-2">
                        @csrf
                        <label class="sr-only" for="refund-qty-{{ $item->id }}">Quantity to refund</label>
                        <select id="refund-qty-{{ $item->id }}" name="quantity" class="rounded-lg border-stone-300 py-1 text-xs focus:border-brand-500 focus:ring-brand-500">
                            @for ($q = 1; $q <= $item->refundableQuantity(); $q++)
                                <option value="{{ $q }}">{{ $q }} × {{ $item->unit_price->multiply($q) }}</option>
                            @endfor
                        </select>
                        <input type="text" name="reason" placeholder="Reason (optional)" maxlength="255" class="w-40 rounded-lg border-stone-300 py-1 text-xs focus:border-brand-500 focus:ring-brand-500">
                        <button class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1 font-semibold text-red-600 transition hover:bg-red-50"><x-icon name="refund" class="h-3.5 w-3.5" /> Refund</button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@endif
