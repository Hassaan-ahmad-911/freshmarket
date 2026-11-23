<x-shop-layout title="Browse products">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold">Browse products</h1>
                <p class="text-sm text-gray-500">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }} from local suppliers</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-4 gap-6">
            <aside class="lg:col-span-1">
                @include('shop.partials.filters', ['action' => route('products.index')])
            </aside>

            <section class="lg:col-span-3">
                @if ($products->isEmpty())
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <p class="text-4xl" aria-hidden="true">🔍</p>
                        <p class="mt-2 font-medium">No products match your filters.</p>
                        <a href="{{ route('products.index') }}" class="mt-2 inline-block text-sm text-emerald-700 hover:underline">Clear filters</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                    <div class="mt-8">{{ $products->links() }}</div>
                @endif
            </section>
        </div>
    </div>
</x-shop-layout>
