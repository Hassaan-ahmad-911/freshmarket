@props(['product'])
<a href="{{ route('products.show', $product) }}"
   class="group flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md hover:border-emerald-200 transition">
    <div class="relative aspect-[4/3]">
        <x-product-image :product="$product" class="absolute inset-0 h-full w-full" />
        @unless ($product->isInStock())
            <span class="absolute top-2 left-2 rounded-full bg-gray-900/80 px-2 py-0.5 text-xs font-medium text-white">Out of stock</span>
        @endunless
    </div>
    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs text-gray-500">{{ $product->supplier->name }}</p>
        <h3 class="mt-1 font-semibold text-gray-900 group-hover:text-emerald-700 line-clamp-2">{{ $product->name }}</h3>
        <div class="mt-auto pt-3 flex items-baseline justify-between">
            <span class="text-lg font-bold">{{ $product->price }}</span>
            @if ($product->unit)
                <span class="text-xs text-gray-500">per {{ $product->unit }}</span>
            @endif
        </div>
    </div>
</a>
