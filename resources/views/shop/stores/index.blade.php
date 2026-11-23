<x-shop-layout title="Stores">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold">Our suppliers</h1>
        <p class="text-sm text-gray-500">Independent farms, bakers and makers selling on FreshMarket.</p>

        @if ($stores->isEmpty())
            <p class="mt-8 text-gray-500">No stores yet.</p>
        @else
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($stores as $store)
                    <a href="{{ route('stores.show', $store) }}" class="flex flex-col rounded-xl bg-white border border-gray-200 p-5 hover:border-emerald-300 hover:shadow-sm transition">
                        <div class="flex items-center gap-3">
                            <x-supplier-logo :supplier="$store" class="h-14 w-14 shrink-0" />
                            <div class="min-w-0">
                                <h2 class="font-semibold truncate">{{ $store->name }}</h2>
                                <p class="text-xs text-gray-500">{{ $counts[$store->id] ?? 0 }} products</p>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-gray-600 line-clamp-2">{{ $store->description }}</p>
                        @if ($store->deliveryAreas->isNotEmpty())
                            <p class="mt-3 text-xs text-gray-500">Delivers to {{ $store->deliveryAreas->pluck('name')->take(4)->join(', ') }}{{ $store->deliveryAreas->count() > 4 ? '…' : '' }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
            <div class="mt-8">{{ $stores->links() }}</div>
        @endif
    </div>
</x-shop-layout>
