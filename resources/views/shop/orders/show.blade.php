<x-shop-layout :title="'Order '.$order->number">
    <div class="container-page max-w-5xl py-10">
        <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> My orders</a>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">Order {{ $order->number }}</h1>
            <x-status-badge :status="$order->status" class="px-3 py-1.5 text-sm" />
        </div>
        <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-stone-500">
            <span class="inline-flex items-center gap-1.5"><x-icon name="clock" class="h-4 w-4" /> Placed {{ $order->created_at->toDayDateTimeString() }}</span>
            <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="h-4 w-4" /> {{ $order->delivery_address }}, {{ $order->delivery_area }}</span>
        </p>

        @includeIf('shop.orders.partials.payment-status')

        <div class="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2" data-reveal-stagger>
                @foreach ($order->supplierOrders as $so)
                    <section class="card overflow-hidden" data-reveal>
                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 bg-stone-50/60 px-5 py-4">
                            <div class="flex items-center gap-3">
                                <x-supplier-logo :supplier="$so->supplier" class="h-10 w-10 text-xs shadow-soft" />
                                <div>
                                    <p class="font-semibold text-ink">{{ $so->supplier->name }}</p>
                                    <p class="text-xs text-stone-500">{{ $so->number }}</p>
                                </div>
                            </div>
                            <x-status-badge :status="$so->status" />
                        </header>
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($so->items as $item)
                                    <tr>
                                        <td class="px-5 py-3 text-ink">{{ $item->quantity }} × {{ $item->product_name }}</td>
                                        <td class="px-5 py-3 text-right text-stone-500">{{ $item->unit_price }} each</td>
                                        <td class="w-28 px-5 py-3 text-right font-semibold text-ink">{{ $item->line_total }}</td>
                                    </tr>
                                    @includeIf('shop.orders.partials.item-refund', ['item' => $item])
                                @endforeach
                            </tbody>
                        </table>
                        <footer class="space-y-1 border-t border-stone-100 px-5 py-3 text-sm">
                            <div class="flex justify-between text-stone-600"><span>Delivery</span><span>{{ $so->delivery_fee->isZero() ? 'Free' : $so->delivery_fee }}</span></div>
                            <div class="flex justify-between font-semibold text-ink"><span>{{ $so->supplier->name }} total</span><span>{{ $so->total }}</span></div>
                            @includeIf('shop.orders.partials.supplier-order-refunds', ['so' => $so])
                        </footer>
                    </section>
                @endforeach
            </div>

            <aside class="card space-y-2 p-6 text-sm lg:sticky lg:top-24">
                <h2 class="mb-2 text-lg font-semibold text-ink">Summary</h2>
                <div class="flex justify-between text-stone-600"><span>Items</span><span>{{ $order->subtotal }}</span></div>
                <div class="flex justify-between text-stone-600"><span>Delivery</span><span>{{ $order->delivery_total }}</span></div>
                <div class="flex justify-between border-t border-stone-100 pt-2 text-base font-bold text-ink"><span>Total paid</span><span>{{ $order->total }}</span></div>
                @includeIf('shop.orders.partials.order-refund-total')
            </aside>
        </div>
    </div>
</x-shop-layout>
