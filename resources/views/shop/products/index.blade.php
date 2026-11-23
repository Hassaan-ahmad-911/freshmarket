<x-shop-layout title="Browse products">
    <section class="border-b border-stone-200/70 bg-gradient-to-b from-brand-50/60 to-transparent">
        <div class="container-page py-10">
            <p class="eyebrow">The market</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">Browse products</h1>
            <p class="mt-2 text-sm text-stone-500">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} from local suppliers</p>
        </div>
    </section>

    <div class="container-page mt-8 grid grid-cols-1 gap-8 lg:grid-cols-4">
        <aside class="lg:col-span-1">
            @include('shop.partials.filters', ['action' => route('products.index')])
        </aside>

        <section class="lg:col-span-3">
            @if ($products->isEmpty())
                <x-empty-state icon="search" title="No products match your filters." text="Try a different search, or clear the filters to see everything in the market.">
                    <a href="{{ route('products.index') }}" class="btn-secondary">Clear filters</a>
                </x-empty-state>
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
</x-shop-layout>
