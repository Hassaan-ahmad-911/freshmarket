<x-shop-layout :title="$product->name">
    <div class="container-page py-8">
        <nav class="flex items-center gap-1.5 text-sm text-stone-500" aria-label="Breadcrumb">
            <a href="{{ route('products.index') }}" class="hover:text-brand-700">Products</a>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-brand-700">{{ $product->category->name }}</a>
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-10 lg:grid-cols-2" data-product>
            {{-- Gallery: click a thumbnail to swap the main image (Alpine.js). --}}
            <div x-data="{ active: @js($product->mainImageUrl()) }" class="animate-fade-up">
                <div class="group relative aspect-square overflow-hidden rounded-3xl border border-stone-200/80 bg-white shadow-soft">
                    @if ($product->images->isNotEmpty())
                        <img :src="active" src="{{ $product->mainImageUrl() }}" alt="{{ $product->name }}" data-product-image
                             class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                    @else
                        <x-product-image :product="$product" :eager="true" class="h-full w-full" />
                    @endif
                </div>
                @if ($product->images->count() > 1)
                    <div class="mt-4 grid grid-cols-5 gap-3">
                        @foreach ($product->images as $image)
                            <button type="button" @click="active = @js($image->url())"
                                    class="aspect-square overflow-hidden rounded-xl ring-2 ring-offset-2 transition"
                                    :class="active === @js($image->url()) ? 'ring-brand-500' : 'ring-transparent opacity-70 hover:opacity-100'">
                                <img src="{{ $image->url() }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="animate-fade-up [animation-delay:120ms]">
                <a href="{{ route('stores.show', $product->supplier) }}" class="inline-flex items-center gap-2 rounded-full bg-white py-1 pl-1 pr-3 text-sm font-medium text-stone-600 shadow-soft ring-1 ring-stone-200 transition hover:text-brand-700">
                    <x-supplier-logo :supplier="$product->supplier" class="h-7 w-7 text-[10px] ring-1" />
                    {{ $product->supplier->name }}
                </a>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ $product->name }}</h1>
                <x-rating :product="$product" size="lg" class="mt-3 text-sm" />

                <p class="mt-6 text-4xl font-extrabold tracking-tight text-ink">
                    {{ $product->price }}
                    @if ($product->unit)<span class="text-base font-medium text-stone-500">/ {{ $product->unit }}</span>@endif
                </p>

                <p @class([
                    'mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium',
                    'bg-brand-50 text-brand-700' => $product->isInStock() && ! $product->isLowStock(),
                    'bg-amber-50 text-amber-700' => $product->isInStock() && $product->isLowStock(),
                    'bg-red-50 text-red-600' => ! $product->isInStock(),
                ])>
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                    {{ $product->isInStock() ? ($product->isLowStock() ? "Only {$product->stock} left" : 'In stock') : 'Out of stock' }}
                </p>

                @if ($product->description)
                    <p class="mt-6 max-w-prose whitespace-pre-line text-[15px] leading-relaxed text-stone-600">{{ $product->description }}</p>
                @endif

                <div class="mt-8">
                    @if (! $product->isInStock())
                        <p class="card p-4 text-sm text-stone-600">Out of stock — check back soon.</p>
                    @elseif (auth()->guest())
                        <a href="{{ route('login') }}" class="btn-primary rounded-full px-6 py-3.5 text-base">Log in to add to basket</a>
                    @elseif (auth()->user()->isCustomer())
                        <form method="POST" action="{{ route('cart.store', $product) }}" @submit.prevent="flyToCart($el)"
                              x-data="{ qty: 1, max: {{ min($product->stock, 99) }} }" class="flex flex-wrap items-center gap-3">
                            @csrf
                            <div class="flex items-center rounded-full border border-stone-300 bg-white p-1">
                                <button type="button" @click="qty = Math.max(1, qty - 1)" class="flex h-10 w-10 items-center justify-center rounded-full text-lg text-stone-600 hover:bg-stone-100" aria-label="Decrease quantity">−</button>
                                <label for="quantity" class="sr-only">Quantity</label>
                                <input id="quantity" type="number" name="quantity" x-model.number="qty" min="1" :max="max" value="1"
                                       class="w-12 border-0 bg-transparent p-0 text-center font-semibold focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" @click="qty = Math.min(max, qty + 1)" class="flex h-10 w-10 items-center justify-center rounded-full text-lg text-stone-600 hover:bg-stone-100" aria-label="Increase quantity">+</button>
                            </div>
                            <button class="btn-primary rounded-full px-7 py-3.5 text-base"><x-icon name="basket" /> Add to basket</button>
                        </form>
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    @endif
                </div>

                <dl class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="card flex gap-3 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="bag" /></span>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-stone-500">Minimum order</dt>
                            <dd class="mt-0.5 font-semibold text-ink">{{ $product->supplier->min_order_amount->isZero() ? 'None' : $product->supplier->min_order_amount }}</dd>
                        </div>
                    </div>
                    <div class="card flex gap-3 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="truck" /></span>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-stone-500">Delivers to</dt>
                            <dd class="mt-0.5 font-semibold text-ink">{{ $product->supplier->deliveryAreas->pluck('name')->join(', ') ?: 'Ask the supplier' }}</dd>
                        </div>
                    </div>
                </dl>
            </div>
        </div>

        @if ($moreFromStore->isNotEmpty())
            <section class="mt-20" data-reveal>
                <div class="flex items-end justify-between">
                    <h2 class="section-title">More from {{ $product->supplier->name }}</h2>
                    <a href="{{ route('stores.show', $product->supplier) }}" class="hidden items-center gap-1 text-sm font-semibold text-brand-700 sm:flex">Visit store <x-icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-4" data-reveal-stagger>
                    @foreach ($moreFromStore as $other)
                        <div data-reveal><x-product-card :product="$other" /></div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-shop-layout>
