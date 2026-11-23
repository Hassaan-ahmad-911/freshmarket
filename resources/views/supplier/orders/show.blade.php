<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('supplier.orders.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> All orders</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-extrabold tracking-tight text-ink">Order {{ $supplierOrder->number }}</h2>
            <x-status-badge :status="$supplierOrder->status" class="px-3 py-1.5 text-sm" />
        </div>
        <p class="mt-1 text-sm text-stone-500">Placed {{ $supplierOrder->created_at->toDayDateTimeString() }}</p>
    </x-slot>

    @php
        $S = \App\Domain\Orders\Enums\SupplierOrderStatus::class;
        $steps = [$S::Pending, $S::Accepted, $S::Preparing, $S::Dispatched, $S::Delivered];
        $current = array_search($supplierOrder->status, $steps, true);
        $cancelled = $supplierOrder->status === $S::Cancelled;
        $pageError = collect($errors->getMessages())->except(['refund'])->flatten()->first();
    @endphp

    <div class="pb-12 pt-6">
        <div class="space-y-6 px-4 sm:px-6 lg:px-0">
            @if ($pageError)
                <div class="flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><x-icon name="warning" /> {{ $pageError }}</div>
            @endif

            {{-- Fulfilment progress --}}
            <div class="card p-5" data-reveal>
                @if ($cancelled)
                    <p class="flex items-center gap-2 text-sm font-medium text-red-600"><x-icon name="x" /> This part of the order was cancelled and refunded.</p>
                @else
                    <ol class="flex items-center">
                        @foreach ($steps as $i => $step)
                            <li class="flex flex-1 items-center {{ $loop->last ? 'flex-none' : '' }}">
                                <span class="flex flex-col items-center gap-1.5">
                                    <span @class([
                                        'flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition-all duration-500',
                                        'bg-brand-600 text-white shadow-glow' => $i <= $current,
                                        'bg-stone-100 text-stone-400' => $i > $current,
                                        'ring-4 ring-brand-100' => $i === $current,
                                    ])>
                                        @if ($i < $current)<x-icon name="check-circle" class="h-5 w-5" />@else{{ $i + 1 }}@endif
                                    </span>
                                    <span class="text-xs font-medium {{ $i <= $current ? 'text-ink' : 'text-stone-400' }}">{{ $step->label() }}</span>
                                </span>
                                @unless ($loop->last)
                                    <span class="mx-2 mb-5 h-0.5 flex-1 rounded-full {{ $i < $current ? 'bg-brand-500' : 'bg-stone-200' }}"></span>
                                @endunless
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                {{-- Items --}}
                <div class="card overflow-hidden xl:col-span-2" data-reveal>
                    <h3 class="border-b border-stone-100 px-5 py-4 font-semibold text-ink">Items</h3>
                    <table class="min-w-full text-sm">
                        <thead class="bg-stone-50/80 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                            <tr><th class="px-5 py-2.5">Product</th><th class="px-5 py-2.5 text-right">Qty</th><th class="px-5 py-2.5 text-right">Price</th><th class="px-5 py-2.5 text-right">Total</th></tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($supplierOrder->items as $item)
                                <tr>
                                    <td class="px-5 py-3">
                                        <span class="flex items-center gap-3">
                                            @if ($item->product)
                                                <x-product-image :product="$item->product" class="h-10 w-10 rounded-lg" />
                                            @endif
                                            <span class="font-medium text-ink">{{ $item->product_name }}</span>
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right text-stone-600">{{ $item->quantity }}</td>
                                    <td class="px-5 py-3 text-right text-stone-600">{{ $item->unit_price }}</td>
                                    <td class="px-5 py-3 text-right font-semibold text-ink">{{ $item->line_total }}</td>
                                </tr>
                                @includeIf('supplier.orders.partials.item-refund-row', ['item' => $item])
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Money --}}
                <div class="card h-fit space-y-2.5 p-5 text-sm" data-reveal>
                    <h3 class="flex items-center gap-2 font-semibold text-ink"><x-icon name="banknotes" class="h-5 w-5 text-brand-600" /> Money</h3>
                    <div class="flex justify-between text-stone-600"><span>Items</span><span>{{ $supplierOrder->subtotal }}</span></div>
                    <div class="flex justify-between text-stone-600"><span>Delivery fee</span><span>{{ $supplierOrder->delivery_fee }}</span></div>
                    <div class="flex justify-between border-t border-stone-100 pt-2.5 font-medium text-ink"><span>Customer paid</span><span>{{ $supplierOrder->total }}</span></div>
                    <div class="flex justify-between text-stone-600"><span>Commission ({{ rtrim(rtrim(number_format($supplierOrder->commission_bps / 100, 2), '0'), '.') }}%)</span><span>− {{ $supplierOrder->commission }}</span></div>
                    <div class="flex justify-between border-t border-stone-100 pt-2.5 text-base font-bold text-ink"><span>Your payout</span><span>{{ $supplierOrder->payout_amount }}</span></div>
                    @includeIf('supplier.orders.partials.money-extra')
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="card p-5 text-sm" data-reveal>
                    <h3 class="flex items-center gap-2 font-semibold text-ink"><x-icon name="map-pin" class="h-5 w-5 text-brand-600" /> Deliver to</h3>
                    <p class="mt-3 leading-relaxed text-stone-700">{{ $supplierOrder->order->delivery_name }}<br>{{ $supplierOrder->order->delivery_address }}<br>{{ $supplierOrder->order->delivery_area }}<br>{{ $supplierOrder->order->delivery_phone }}</p>
                    @if ($supplierOrder->order->delivery_notes)
                        <p class="mt-3 rounded-xl bg-stone-50 p-3 text-stone-600"><strong>Notes:</strong> {{ $supplierOrder->order->delivery_notes }}</p>
                    @endif
                </div>

                <div class="card space-y-3 p-5 text-sm" data-reveal>
                    <h3 class="flex items-center gap-2 font-semibold text-ink"><x-icon name="truck" class="h-5 w-5 text-brand-600" /> Update status</h3>
                    @forelse ($supplierOrder->status->nextSteps() as $next)
                        <form method="POST" action="{{ route('supplier.orders.status', $supplierOrder) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button class="btn-primary w-full">Mark as {{ $next->label() }} <x-icon name="arrow-right" class="h-4 w-4" /></button>
                        </form>
                    @empty
                        <p class="text-stone-500">No further steps.</p>
                    @endforelse
                    @includeIf('supplier.orders.partials.cancel')
                </div>
            </div>

            @includeIf('supplier.orders.partials.payments')
        </div>
    </div>
</x-app-layout>
