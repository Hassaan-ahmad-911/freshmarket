<x-shop-layout title="My orders">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold">My orders</h1>

        @if ($orders->isEmpty())
            <p class="mt-6 text-gray-500">No orders yet. <a href="{{ route('products.index') }}" class="text-emerald-700 hover:underline">Start shopping</a></p>
        @else
            <div class="mt-6 space-y-3">
                @foreach ($orders as $order)
                    <a href="{{ route('orders.show', $order) }}" class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-emerald-300 transition">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-semibold">{{ $order->number }}</span>
                                <span class="ml-2 text-sm text-gray-500">{{ $order->created_at->toDayDateTimeString() }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-status-badge :status="$order->status" />
                                <span class="font-bold">{{ $order->total }}</span>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-600">
                            @foreach ($order->supplierOrders as $so)
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5">{{ $so->supplier->name }}: <strong>{{ $so->status->label() }}</strong></span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
</x-shop-layout>
