@props(['product'])
@php($user = auth()->user())
<article data-product class="group relative flex flex-col overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-soft transition duration-300 ease-out hover:-translate-y-1 hover:border-brand-200 hover:shadow-lift">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-[4/3] overflow-hidden" aria-label="{{ $product->name }}">
        <x-product-image :product="$product" class="absolute inset-0 h-full w-full" />
        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/25 via-transparent to-transparent opacity-0 transition duration-300 group-hover:opacity-100"></span>
        @unless ($product->isInStock())
            <span class="absolute left-3 top-3 rounded-full bg-ink/80 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur">Out of stock</span>
        @elseif ($product->isLowStock())
            <span class="absolute left-3 top-3 rounded-full bg-amber-500/90 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur">Only {{ $product->stock }} left</span>
        @endunless
    </a>

    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('stores.show', $product->supplier) }}" class="inline-flex max-w-full items-center gap-1.5 self-start rounded-full bg-stone-100 py-0.5 pl-0.5 pr-2.5 text-xs font-medium text-stone-600 transition hover:bg-brand-50 hover:text-brand-700">
            <x-supplier-logo :supplier="$product->supplier" class="h-5 w-5 text-[8px] ring-1" />
            <span class="truncate">{{ $product->supplier->name }}</span>
        </a>

        <a href="{{ route('products.show', $product) }}" class="mt-2">
            <h3 class="line-clamp-2 font-semibold leading-snug text-ink transition group-hover:text-brand-700">{{ $product->name }}</h3>
        </a>
        <x-rating :product="$product" class="mt-1.5" />

        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <div>
                <p class="text-lg font-bold tracking-tight text-ink">{{ $product->price }}</p>
                @if ($product->unit)
                    <p class="text-xs text-stone-500">per {{ $product->unit }}</p>
                @endif
            </div>

            @if ($product->isInStock())
                @if (! $user)
                    <a href="{{ route('login') }}" class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700 transition hover:bg-brand-600 hover:text-white" aria-label="Log in to add {{ $product->name }}">
                        <x-icon name="plus" />
                    </a>
                @elseif ($user->isCustomer())
                    <form method="POST" action="{{ route('cart.store', $product) }}" @submit.prevent="flyToCart($el)">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm transition hover:scale-110 hover:bg-brand-700 hover:shadow-glow active:scale-95" aria-label="Add {{ $product->name }} to basket">
                            <x-icon name="plus" />
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</article>
