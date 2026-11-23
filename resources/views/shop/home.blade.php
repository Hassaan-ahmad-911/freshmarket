<x-shop-layout>
    {{-- ============ Hero ============ --}}
    <section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16">
        <img src="{{ asset('images/hero.jpg') }}" alt="" fetchpriority="high"
             class="absolute inset-0 -z-10 h-full w-full animate-slow-zoom object-cover">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-black/80 via-black/55 to-black/10"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-black/50 to-transparent"></div>

        <div class="container-page py-24 sm:py-32 lg:py-40">
            <div class="max-w-2xl">
                <p class="inline-flex animate-fade-up items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-brand-200 ring-1 ring-white/20 backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-400"></span> Local · Seasonal · Independent
                </p>
                <h1 class="mt-6 animate-fade-up text-4xl font-extrabold leading-[1.05] tracking-tight text-white [animation-delay:120ms] sm:text-6xl">
                    Fresh food from independent local suppliers.
                </h1>
                <p class="mt-6 max-w-xl animate-fade-up text-lg leading-relaxed text-stone-200 [animation-delay:240ms]">
                    Fill one basket from farms, bakeries and dairies near you — and pay once. Every supplier gets their share, automatically.
                </p>
                <div class="mt-10 flex animate-fade-up flex-wrap gap-3 [animation-delay:360ms]">
                    <a href="{{ route('products.index') }}" class="btn-primary rounded-full px-6 py-3.5 text-base">
                        Start shopping <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('stores.index') }}" class="btn rounded-full bg-white/10 px-6 py-3.5 text-base text-white ring-1 ring-white/30 backdrop-blur hover:bg-white/20">
                        Meet the suppliers
                    </a>
                </div>
            </div>

            {{-- Trust badges --}}
            <dl class="mt-16 grid max-w-3xl animate-fade-up grid-cols-1 gap-3 [animation-delay:480ms] sm:grid-cols-3">
                @foreach ([
                    ['store', 'Independent suppliers', 'Farms and makers near you'],
                    ['card', 'One basket, one payment', 'Mix stores in one checkout'],
                    ['shield-check', 'Secure payments', 'Processed by Stripe'],
                ] as [$icon, $label, $text])
                    <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-500/90 text-white"><x-icon :name="$icon" /></span>
                        <div>
                            <dt class="text-sm font-semibold text-white">{{ $label }}</dt>
                            <dd class="text-xs text-stone-300">{{ $text }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ============ Categories ============ --}}
    @if ($categories->isNotEmpty())
        <section class="container-page mt-20" data-reveal>
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Browse the market</p>
                    <h2 class="section-title mt-2">Shop by category</h2>
                </div>
                <a href="{{ route('products.index') }}" class="hidden items-center gap-1 text-sm font-semibold text-brand-700 hover:gap-2 sm:flex transition-all">All products <x-icon name="arrow-right" class="h-4 w-4" /></a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" data-reveal-stagger>
                @foreach ($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}" data-reveal
                       class="group relative aspect-[4/5] overflow-hidden rounded-2xl shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-lift">
                        <x-category-photo :category="$category" class="absolute inset-0 h-full w-full" />
                        <span class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-transparent"></span>
                        <span class="absolute inset-x-0 bottom-0 p-4">
                            <span class="block text-base font-semibold text-white">{{ $category->name }}</span>
                            <span class="mt-0.5 flex items-center gap-1 text-xs text-white/80 opacity-0 transition group-hover:opacity-100">Shop now <x-icon name="arrow-right" class="h-3 w-3" /></span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ Featured products ============ --}}
    <section class="container-page mt-24" data-reveal>
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Just in</p>
                <h2 class="section-title mt-2">Featured products</h2>
            </div>
            <a href="{{ route('products.index') }}" class="flex items-center gap-1 text-sm font-semibold text-brand-700 transition-all hover:gap-2">View all <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        @if ($latestProducts->isEmpty())
            <x-empty-state icon="sparkles" title="No products yet" text="Our suppliers are stocking up — check back soon." class="mt-8" />
        @else
            <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4" data-reveal-stagger>
                @foreach ($latestProducts as $product)
                    <div data-reveal><x-product-card :product="$product" /></div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- ============ Top suppliers (carousel) ============ --}}
    @if ($stores->isNotEmpty())
        <section class="mt-24 bg-gradient-to-b from-brand-50/70 to-transparent py-20" data-reveal x-data="carousel">
            <div class="container-page">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Meet the makers</p>
                        <h2 class="section-title mt-2">Top suppliers</h2>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="go(-1)" :disabled="atStart" class="flex h-10 w-10 items-center justify-center rounded-full border border-stone-300 bg-white text-stone-700 transition hover:bg-stone-50 disabled:opacity-40" aria-label="Previous suppliers"><x-icon name="chevron-left" /></button>
                        <button type="button" @click="go(1)" :disabled="atEnd" class="flex h-10 w-10 items-center justify-center rounded-full border border-stone-300 bg-white text-stone-700 transition hover:bg-stone-50 disabled:opacity-40" aria-label="Next suppliers"><x-icon name="chevron-right" /></button>
                    </div>
                </div>

                <div x-ref="track" @scroll.debounce.50ms="update"
                     class="-mx-4 mt-8 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-4 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($stores as $store)
                        <a href="{{ route('stores.show', $store) }}"
                           class="group w-[85%] shrink-0 snap-start overflow-hidden rounded-3xl border border-stone-200/80 bg-white shadow-soft transition duration-300 hover:-translate-y-1 hover:shadow-lift sm:w-[420px]">
                            <div class="relative h-40">
                                <x-supplier-cover :supplier="$store" class="h-full w-full" />
                                <span class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></span>
                            </div>
                            <div class="relative px-6 pb-6">
                                <x-supplier-logo :supplier="$store" class="-mt-9 h-16 w-16 text-lg shadow-lg ring-4" />
                                <h3 class="mt-3 text-lg font-bold text-ink group-hover:text-brand-700">{{ $store->name }}</h3>
                                <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ $store->description }}</p>
                                <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 transition-all group-hover:gap-2">Visit store <x-icon name="arrow-right" class="h-4 w-4" /></span>
                            </div>
                        </a>
                    @endforeach
                    <a href="{{ route('supplier.register') }}"
                       class="flex w-[85%] shrink-0 snap-start flex-col items-center justify-center rounded-3xl border-2 border-dashed border-brand-300 bg-white/60 p-8 text-center transition hover:border-brand-500 hover:bg-white sm:w-[320px]">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-100 text-brand-700"><x-icon name="store" class="h-7 w-7" /></span>
                        <span class="mt-4 font-semibold text-ink">Grow food or bake?</span>
                        <span class="mt-1 text-sm text-stone-500">Open your own store on FreshMarket.</span>
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ How it works ============ --}}
    <section id="how-it-works" class="container-page mt-20 scroll-mt-24" data-reveal>
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">How it works</p>
            <h2 class="section-title mt-2">From their field to your table in three steps</h2>
        </div>
        <ol class="mt-12 grid gap-6 md:grid-cols-3" data-reveal-stagger>
            @foreach ([
                ['basket', 'Fill one basket', 'Mix produce, bread and dairy from as many local suppliers as you like.'],
                ['card', 'Pay once', 'One secure checkout. We split the payment so every supplier gets their share.'],
                ['truck', 'Fresh to your door', 'Each supplier prepares and delivers their part — track every step live.'],
            ] as $i => [$icon, $step, $text])
                <li data-reveal class="card relative p-8">
                    <span class="absolute right-6 top-5 text-5xl font-extrabold text-stone-100">{{ $i + 1 }}</span>
                    <span class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-glow"><x-icon :name="$icon" class="h-6 w-6" /></span>
                    <h3 class="relative mt-6 text-lg font-semibold text-ink">{{ $step }}</h3>
                    <p class="relative mt-2 text-sm leading-relaxed text-stone-500">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ============ Testimonials ============ --}}
    <section class="container-page mt-24" data-reveal>
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Loved locally</p>
            <h2 class="section-title mt-2">What our customers say</h2>
        </div>
        <div class="mt-12 grid gap-6 md:grid-cols-3" data-reveal-stagger>
            @foreach ([
                ['Priya S.', 'Islington', 'I get my veg from the farm and my bread from the bakery in one order. It has completely changed my weekly shop.'],
                ['Tom W.', 'Camden', 'You can taste the difference. Knowing exactly who grew or baked what makes it feel like a proper market.'],
                ['Amara K.', 'Hackney', 'Checkout is so simple — one payment and both suppliers delivered the same morning. Brilliant service.'],
            ] as [$name, $area, $quote])
                <figure data-reveal class="card flex flex-col p-7">
                    <div class="flex gap-0.5 text-amber-400">
                        @for ($s = 0; $s < 5; $s++)<x-icon name="star" solid class="h-4 w-4" />@endfor
                    </div>
                    <blockquote class="mt-4 flex-1 text-[15px] leading-relaxed text-stone-700">“{{ $quote }}”</blockquote>
                    <figcaption class="mt-6 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ collect(explode(' ', $name))->map(fn ($w) => $w[0])->implode('') }}</span>
                        <span>
                            <span class="block text-sm font-semibold text-ink">{{ $name }}</span>
                            <span class="block text-xs text-stone-500">{{ $area }}</span>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
        <p class="mt-6 text-center text-xs text-stone-400">Sample reviews for this demo marketplace.</p>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="container-page mt-24" data-reveal>
        <div class="relative overflow-hidden rounded-4xl bg-brand-700 px-8 py-14 text-center sm:px-16">
            <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-brand-500/40 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-16 h-72 w-72 rounded-full bg-brand-900/50 blur-3xl"></div>
            <h2 class="relative text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Ready for a fresher week?</h2>
            <p class="relative mx-auto mt-3 max-w-lg text-brand-100">Browse what your local suppliers have in today and fill your basket in minutes.</p>
            <a href="{{ route('products.index') }}" class="btn relative mt-8 rounded-full bg-white px-6 py-3.5 text-base text-brand-800 hover:bg-brand-50">Start shopping <x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
    </section>
</x-shop-layout>
