<x-shop-layout :title="$product->name">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <nav class="text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('products.index') }}" class="hover:text-gray-700">Products</a>
            <span class="mx-1">/</span>
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-gray-700">{{ $product->category->name }}</a>
        </nav>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Gallery: click a thumbnail to swap the main image (Alpine.js). --}}
            <div x-data="{ active: @js($product->mainImageUrl()) }">
                <div class="aspect-square rounded-2xl overflow-hidden border border-gray-200 bg-white">
                    @if ($product->images->isNotEmpty())
                        <img :src="active" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    @else
                        <x-product-image :product="$product" class="h-full w-full" />
                    @endif
                </div>
                @if ($product->images->count() > 1)
                    <div class="mt-3 grid grid-cols-6 gap-2">
                        @foreach ($product->images as $image)
                            <button type="button" @click="active = @js($image->url())"
                                    class="aspect-square rounded-lg overflow-hidden border-2"
                                    :class="active === @js($image->url()) ? 'border-emerald-500' : 'border-transparent'">
                                <img src="{{ $image->url() }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <a href="{{ route('stores.show', $product->supplier) }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-emerald-700">
                    <x-supplier-logo :supplier="$product->supplier" class="h-6 w-6 text-[10px]" />
                    {{ $product->supplier->name }}
                </a>
                <h1 class="mt-2 text-3xl font-bold">{{ $product->name }}</h1>
                <p class="mt-3 text-3xl font-bold text-emerald-700">
                    {{ $product->price }}
                    @if ($product->unit)<span class="text-base font-normal text-gray-500">/ {{ $product->unit }}</span>@endif
                </p>

                <p class="mt-2 text-sm {{ $product->isInStock() ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ $product->isInStock() ? ($product->isLowStock() ? "Only {$product->stock} left" : 'In stock') : 'Out of stock' }}
                </p>

                @if ($product->description)
                    <div class="mt-6 text-gray-700 leading-relaxed whitespace-pre-line">{{ $product->description }}</div>
                @endif

                <div class="mt-8 rounded-xl bg-gray-100 p-4 text-sm text-gray-600">
                    Add to basket arrives with the cart in Phase 3.
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Minimum order</dt>
                        <dd class="font-medium">{{ $product->supplier->min_order_amount->isZero() ? 'None' : $product->supplier->min_order_amount }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Delivers to</dt>
                        <dd class="font-medium">{{ $product->supplier->deliveryAreas->pluck('name')->join(', ') ?: 'Ask the supplier' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @if ($moreFromStore->isNotEmpty())
            <section class="mt-16">
                <h2 class="text-xl font-bold">More from {{ $product->supplier->name }}</h2>
                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach ($moreFromStore as $other)
                        <x-product-card :product="$other" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-shop-layout>
