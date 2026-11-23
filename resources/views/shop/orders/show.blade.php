<x-shop-layout :title="'Order '.$order->number">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <a href="{{ route('orders.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; My orders</a>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">Order {{ $order->number }}</h1>
            <x-status-badge :status="$order->status" class="text-sm" />
        </div>
        <p class="text-sm text-gray-500">Placed {{ $order->created_at->toDayDateTimeString() }} · Delivering to {{ $order->delivery_address }}, {{ $order->delivery_area }}</p>

        @includeIf('shop.orders.partials.payment-status')

        <div class="mt-6 space-y-4">
            @foreach ($order->supplierOrders as $so)
                <section class="bg-white rounded-xl border border-gray-200">
                    <header class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">{{ $so->supplier->name }}</span>
                            <span class="text-xs text-gray-500">{{ $so->number }}</span>
                        </div>
                        <x-status-badge :status="$so->status" />
                    </header>
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($so->items as $item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item->quantity }} × {{ $item->product_name }}</td>
                                    <td class="px-4 py-2 text-right text-gray-500">{{ $item->unit_price }} each</td>
                                    <td class="px-4 py-2 text-right font-medium w-28">{{ $item->line_total }}</td>
                                </tr>
                                @includeIf('shop.orders.partials.item-refund', ['item' => $item])
                            @endforeach
                        </tbody>
                    </table>
                    <footer class="px-4 py-3 border-t border-gray-100 text-sm space-y-1">
                        <div class="flex justify-between text-gray-600"><span>Delivery</span><span>{{ $so->delivery_fee->isZero() ? 'Free' : $so->delivery_fee }}</span></div>
                        <div class="flex justify-between font-semibold"><span>{{ $so->supplier->name }} total</span><span>{{ $so->total }}</span></div>
                        @includeIf('shop.orders.partials.supplier-order-refunds', ['so' => $so])
                    </footer>
                </section>
            @endforeach
        </div>

        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-4 text-sm space-y-1 max-w-sm ml-auto">
            <div class="flex justify-between"><span>Items</span><span>{{ $order->subtotal }}</span></div>
            <div class="flex justify-between"><span>Delivery</span><span>{{ $order->delivery_total }}</span></div>
            <div class="flex justify-between text-base font-bold border-t border-gray-100 pt-1"><span>Total paid</span><span>{{ $order->total }}</span></div>
            @includeIf('shop.orders.partials.order-refund-total')
        </div>
    </div>
</x-shop-layout>
