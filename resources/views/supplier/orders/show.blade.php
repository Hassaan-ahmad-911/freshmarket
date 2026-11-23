<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Order {{ $supplierOrder->number }}</h2>
                <x-status-badge :status="$supplierOrder->status" />
            </div>
            <a href="{{ route('supplier.orders.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; All orders</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Items --}}
                <div class="md:col-span-2 bg-white shadow-sm sm:rounded-lg">
                    <h3 class="px-5 py-3 border-b border-gray-100 font-semibold">Items</h3>
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr><th class="px-5 py-2">Product</th><th class="px-5 py-2 text-right">Qty</th><th class="px-5 py-2 text-right">Price</th><th class="px-5 py-2 text-right">Total</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($supplierOrder->items as $item)
                                <tr>
                                    <td class="px-5 py-2">{{ $item->product_name }}</td>
                                    <td class="px-5 py-2 text-right">{{ $item->quantity }}</td>
                                    <td class="px-5 py-2 text-right">{{ $item->unit_price }}</td>
                                    <td class="px-5 py-2 text-right font-medium">{{ $item->line_total }}</td>
                                </tr>
                                @includeIf('supplier.orders.partials.item-refund-row', ['item' => $item])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Money --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-5 text-sm space-y-2 h-fit">
                    <h3 class="font-semibold">Money</h3>
                    <div class="flex justify-between"><span>Items</span><span>{{ $supplierOrder->subtotal }}</span></div>
                    <div class="flex justify-between"><span>Delivery fee</span><span>{{ $supplierOrder->delivery_fee }}</span></div>
                    <div class="flex justify-between font-medium border-t border-gray-100 pt-2"><span>Customer paid</span><span>{{ $supplierOrder->total }}</span></div>
                    <div class="flex justify-between text-gray-600"><span>Commission ({{ rtrim(rtrim(number_format($supplierOrder->commission_bps / 100, 2), '0'), '.') }}%)</span><span>− {{ $supplierOrder->commission }}</span></div>
                    <div class="flex justify-between font-bold border-t border-gray-100 pt-2"><span>Your payout</span><span>{{ $supplierOrder->payout_amount }}</span></div>
                    @includeIf('supplier.orders.partials.money-extra')
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-5 text-sm">
                    <h3 class="font-semibold">Deliver to</h3>
                    <p class="mt-2">{{ $supplierOrder->order->delivery_name }}<br>{{ $supplierOrder->order->delivery_address }}<br>{{ $supplierOrder->order->delivery_area }}<br>{{ $supplierOrder->order->delivery_phone }}</p>
                    @if ($supplierOrder->order->delivery_notes)
                        <p class="mt-2 text-gray-600"><strong>Notes:</strong> {{ $supplierOrder->order->delivery_notes }}</p>
                    @endif
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-5 text-sm space-y-3">
                    <h3 class="font-semibold">Update status</h3>
                    @forelse ($supplierOrder->status->nextSteps() as $next)
                        <form method="POST" action="{{ route('supplier.orders.status', $supplierOrder) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">Mark as {{ $next->label() }}</button>
                        </form>
                    @empty
                        <p class="text-gray-500">No further steps.</p>
                    @endforelse
                    @includeIf('supplier.orders.partials.cancel')
                </div>
            </div>

            @includeIf('supplier.orders.partials.payments')
        </div>
    </div>
</x-app-layout>
