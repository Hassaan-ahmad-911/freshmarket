<x-shop-layout title="Stores">
    <section class="border-b border-stone-200/70 bg-gradient-to-b from-brand-50/60 to-transparent">
        <div class="container-page py-10">
            <p class="eyebrow">Meet the makers</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Our suppliers</h1>
            <p class="mt-2 text-sm text-stone-500">Independent farms, bakers and makers selling on FreshMarket.</p>
        </div>
    </section>

    <div class="container-page mt-8">
        @if ($stores->isEmpty())
            <x-empty-state icon="store" title="No stores yet" text="The first suppliers are setting up shop." />
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger>
                @foreach ($stores as $store)
                    <a href="{{ route('stores.show', $store) }}" data-reveal
                       class="group flex flex-col overflow-hidden rounded-3xl border border-stone-200/80 bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-lift">
                        <div class="relative h-36">
                            <x-supplier-cover :supplier="$store" class="h-full w-full" />
                            <span class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></span>
                            <span class="absolute right-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-stone-700 backdrop-blur">{{ $counts[$store->id] ?? 0 }} products</span>
                        </div>
                        <div class="flex flex-1 flex-col px-6 pb-6">
                            <x-supplier-logo :supplier="$store" class="-mt-8 h-16 w-16 text-lg shadow-lg ring-4" />
                            <h2 class="mt-3 text-lg font-bold text-ink group-hover:text-brand-700">{{ $store->name }}</h2>
                            <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ $store->description }}</p>
                            @if ($store->deliveryAreas->isNotEmpty())
                                <p class="mt-4 flex items-start gap-1.5 text-xs text-stone-500">
                                    <x-icon name="map-pin" class="mt-px h-4 w-4 text-brand-600" />
                                    Delivers to {{ $store->deliveryAreas->pluck('name')->take(4)->join(', ') }}{{ $store->deliveryAreas->count() > 4 ? '…' : '' }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="mt-10">{{ $stores->links() }}</div>
        @endif
    </div>
</x-shop-layout>
