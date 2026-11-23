<x-shop-layout :title="$supplier->name">
    <section class="relative">
        <x-supplier-cover :supplier="$supplier" class="h-56 w-full sm:h-72" />
        <span class="absolute inset-0 bg-gradient-to-t from-black/50 via-black/10 to-transparent"></span>
    </section>

    <div class="container-page">
        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-end sm:gap-6">
            <x-supplier-logo :supplier="$supplier" class="-mt-16 h-32 w-32 shrink-0 text-3xl shadow-lift ring-[6px] animate-fade-up" />
            <div class="flex-1 animate-fade-up pt-2 [animation-delay:100ms] sm:pb-1">
                <h1 class="text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ $supplier->name }}</h1>
                @if ($supplier->description)
                    <p class="mt-2 max-w-2xl text-stone-600">{{ $supplier->description }}</p>
                @endif
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-stone-600 shadow-soft ring-1 ring-stone-200">
                <x-icon name="bag" class="h-4 w-4 text-brand-600" />
                Minimum order: <strong class="text-ink">{{ $supplier->min_order_amount->isZero() ? 'none' : $supplier->min_order_amount }}</strong>
            </span>
            @if ($supplier->deliveryAreas->isNotEmpty())
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-stone-600 shadow-soft ring-1 ring-stone-200">
                    <x-icon name="truck" class="h-4 w-4 text-brand-600" />
                    Delivers to: <strong class="text-ink">{{ $supplier->deliveryAreas->pluck('name')->join(', ') }}</strong>
                </span>
            @endif
        </div>

        <div class="mt-10 grid grid-cols-1 gap-8 lg:grid-cols-4">
            <aside>
                @include('shop.partials.filters', ['action' => route('stores.show', $supplier), 'hideArea' => true])
            </aside>
            <section class="lg:col-span-3">
                @if ($products->isEmpty())
                    <x-empty-state icon="search" title="No products found" text="Try another search or category." />
                @else
                    <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3" data-reveal-stagger>
                        @foreach ($products as $product)
                            <div data-reveal><x-product-card :product="$product" /></div>
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-shop-layout>
