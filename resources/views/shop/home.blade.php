<x-shop-layout>
    {{-- Hero --}}
    <section class="bg-gradient-to-br from-emerald-700 to-emerald-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <h1 class="text-3xl sm:text-5xl font-bold tracking-tight max-w-2xl">Fresh food from independent local suppliers.</h1>
            <p class="mt-4 text-emerald-100 text-lg max-w-xl">Fill one basket from farms, bakeries and dairies near you — and pay once.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('products.index') }}" class="rounded-lg bg-white px-5 py-3 font-semibold text-emerald-800 hover:bg-emerald-50">Start shopping</a>
                <a href="{{ route('stores.index') }}" class="rounded-lg border border-emerald-300/60 px-5 py-3 font-semibold hover:bg-emerald-800">Meet the suppliers</a>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Categories --}}
        @if ($categories->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-xl font-bold">Shop by category</h2>
                <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    @foreach ($categories as $category)
                        <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                           class="flex flex-col items-center gap-2 rounded-xl bg-white border border-gray-200 p-4 hover:border-emerald-300 hover:shadow-sm transition">
                            <span class="text-3xl" aria-hidden="true">{{ $category->icon }}</span>
                            <span class="text-sm font-medium text-center">{{ $category->name }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Latest products --}}
        <section class="mt-12">
            <div class="flex items-end justify-between">
                <h2 class="text-xl font-bold">Just added</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">View all &rarr;</a>
            </div>
            @if ($latestProducts->isEmpty())
                <p class="mt-4 text-gray-500">No products yet — check back soon.</p>
            @else
                <div class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($latestProducts as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Stores --}}
        @if ($stores->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-xl font-bold">Our suppliers</h2>
                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($stores as $store)
                        <a href="{{ route('stores.show', $store) }}" class="flex items-center gap-3 rounded-xl bg-white border border-gray-200 p-4 hover:border-emerald-300 transition">
                            <x-supplier-logo :supplier="$store" class="h-12 w-12 shrink-0" />
                            <div class="min-w-0">
                                <p class="font-semibold truncate">{{ $store->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($store->description, 50) }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-shop-layout>
