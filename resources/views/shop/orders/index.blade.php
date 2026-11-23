<x-shop-layout title="My orders">
    <div class="container-page max-w-5xl py-10">
        <h1 class="text-3xl font-extrabold tracking-tight text-ink">My orders</h1>

        @if ($orders->isEmpty())
            <x-empty-state icon="bag" title="No orders yet" text="When you place an order, you can follow every supplier's progress here." class="mt-8">
                <a href="{{ route('products.index') }}" class="btn-primary rounded-full px-6">Start shopping</a>
            </x-empty-state>
        @else
            <div class="mt-8 space-y-4" data-reveal-stagger>
                @foreach ($orders as $order)
                    <a href="{{ route('orders.show', $order) }}" data-reveal
                       class="card group block p-5 transition duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lift">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="bag" /></span>
                                <div>
                                    <p class="font-semibold text-ink group-hover:text-brand-700">{{ $order->number }}</p>
                                    <p class="text-xs text-stone-500">{{ $order->created_at->toDayDateTimeString() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <x-status-badge :status="$order->status" />
                                <span class="text-lg font-bold text-ink">{{ $order->total }}</span>
                                <x-icon name="chevron-right" class="h-5 w-5 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-stone-100 pt-4">
                            @foreach ($order->supplierOrders as $so)
                                <span class="inline-flex items-center gap-2 rounded-full bg-stone-50 py-1 pl-1 pr-1 text-xs text-stone-600 ring-1 ring-stone-200">
                                    <x-supplier-logo :supplier="$so->supplier" class="h-5 w-5 text-[8px] ring-1" />
                                    {{ $so->supplier->name }}
                                    <x-status-badge :status="$so->status" class="py-0.5" />
                                </span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $orders->links() }}</div>
        @endif
    </div>
</x-shop-layout>
