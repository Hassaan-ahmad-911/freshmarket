@props(['title' => null, 'cartCount' => 0, 'transparentHeader' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="font-sans">
<div class="flex min-h-screen flex-col animate-fade-in">
    @php
        $user = Auth::user();
        // The basket badge "pops" right after something was added (the flash message starts with "Added").
        $justAdded = str_starts_with((string) session('status'), 'Added');
        $navLink = fn (string $pattern) => request()->routeIs($pattern)
            ? 'text-brand-700 bg-brand-50'
            : 'text-stone-600 hover:text-ink hover:bg-stone-100';
    @endphp

    <header x-data="{ open: false, scrolled: false }" @scroll.window="scrolled = window.scrollY > 8"
            class="sticky top-0 z-40 border-b transition-colors duration-300"
            :class="scrolled || open ? 'border-stone-200/80 bg-white/90 backdrop-blur-lg shadow-sm' : 'border-transparent bg-white/70 backdrop-blur'">
        <div class="container-page flex h-16 items-center gap-4 lg:gap-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="FreshMarket home">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-glow">
                    <x-application-logo class="h-5 w-5" />
                </span>
                <span class="text-lg font-extrabold tracking-tight">Fresh<span class="text-brand-600">Market</span></span>
            </a>

            <form action="{{ route('products.index') }}" method="GET" class="relative hidden max-w-md flex-1 md:block">
                <label for="header-search" class="sr-only">Search products</label>
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search fresh food from local suppliers…"
                       class="w-full rounded-full border-stone-200 bg-stone-100/80 py-2.5 pl-10 pr-4 text-sm transition focus:border-brand-500 focus:bg-white focus:ring-brand-500">
            </form>

            <nav class="ms-auto hidden items-center gap-1 text-sm font-medium md:flex">
                <a href="{{ route('products.index') }}" class="rounded-lg px-3 py-2 transition {{ $navLink('products.*') }}">Browse</a>
                <a href="{{ route('stores.index') }}" class="rounded-lg px-3 py-2 transition {{ $navLink('stores.*') }}">Stores</a>
                @auth
                    @if ($user->isCustomer())
                        <a href="{{ route('orders.index') }}" class="rounded-lg px-3 py-2 transition {{ $navLink('orders.*') }}">My orders</a>
                        <a href="{{ route('cart.index') }}" data-cart-target
                           class="relative ms-1 inline-flex items-center gap-2 rounded-full border px-3.5 py-2 transition {{ request()->routeIs('cart.*') ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:shadow-soft' }}">
                            <x-icon name="basket" class="h-5 w-5" />
                            <span>Basket</span>
                            @if ($cartCount > 0)
                                <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[11px] font-bold text-white {{ $justAdded ? 'animate-pop' : '' }}">{{ $cartCount }}</span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-stone-600 transition hover:bg-stone-100 hover:text-ink">{{ $user->isSupplierUser() ? 'My Store' : 'Admin' }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-lg p-2 text-stone-500 transition hover:bg-stone-100 hover:text-ink" title="Log out" aria-label="Log out"><x-icon name="logout" /></button>
                    </form>
                @else
                    <a href="{{ route('supplier.register') }}" class="rounded-lg px-3 py-2 text-stone-600 transition hover:bg-stone-100 hover:text-ink">Sell on FreshMarket</a>
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-stone-600 transition hover:bg-stone-100 hover:text-ink">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary ms-1 rounded-full px-4 py-2">Sign up</a>
                @endauth
            </nav>

            @if ($user?->isCustomer())
                <a href="{{ route('cart.index') }}" data-cart-target class="relative ms-auto rounded-full p-2 text-stone-700 md:hidden" aria-label="Basket">
                    <x-icon name="basket" class="h-6 w-6" />
                    @if ($cartCount > 0)
                        <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white {{ $justAdded ? 'animate-pop' : '' }}">{{ $cartCount }}</span>
                    @endif
                </a>
            @endif
            <button @click="open = !open" class="{{ $user?->isCustomer() ? '' : 'ms-auto' }} rounded-lg p-2 text-stone-600 hover:bg-stone-100 md:hidden" aria-label="Menu" :aria-expanded="open">
                <x-icon name="menu" class="h-6 w-6" x-show="!open" />
                <x-icon name="x" class="h-6 w-6" x-show="open" x-cloak />
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="border-t border-stone-200 bg-white md:hidden">
            <div class="container-page space-y-1 py-4 text-sm font-medium">
                <form action="{{ route('products.index') }}" method="GET" class="relative mb-3">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products…" class="input rounded-full pl-10">
                </form>
                <a href="{{ route('products.index') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">Browse</a>
                <a href="{{ route('stores.index') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">Stores</a>
                @auth
                    @if ($user->isCustomer())
                        <a href="{{ route('cart.index') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">Basket ({{ $cartCount }})</a>
                        <a href="{{ route('orders.index') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">My orders</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">My account</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2.5 text-left text-stone-500 hover:bg-stone-100">Log out</button></form>
                @else
                    <a href="{{ route('supplier.register') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">Sell on FreshMarket</a>
                    <a href="{{ route('login') }}" class="block rounded-lg px-3 py-2.5 hover:bg-stone-100">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary mt-2 w-full">Sign up</a>
                @endauth
            </div>
        </div>
    </header>

    <x-flash />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-stone-200 bg-white">
        <div class="container-page grid grid-cols-2 gap-10 py-14 md:grid-cols-5">
            <div class="col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white"><x-application-logo class="h-5 w-5" /></span>
                    <span class="text-lg font-extrabold tracking-tight">Fresh<span class="text-brand-600">Market</span></span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-stone-500">Fresh food from independent local suppliers. One basket, one payment — every supplier paid their share.</p>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-ink">Shop</h3>
                <ul class="mt-4 space-y-3 text-sm text-stone-500">
                    <li><a href="{{ route('products.index') }}" class="transition hover:text-brand-700">All products</a></li>
                    <li><a href="{{ route('stores.index') }}" class="transition hover:text-brand-700">Our suppliers</a></li>
                    <li><a href="{{ route('home') }}#how-it-works" class="transition hover:text-brand-700">How it works</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-ink">Sell</h3>
                <ul class="mt-4 space-y-3 text-sm text-stone-500">
                    <li><a href="{{ route('supplier.register') }}" class="transition hover:text-brand-700">Open a store</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-brand-700">Supplier login</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-ink">Project</h3>
                <ul class="mt-4 space-y-3 text-sm text-stone-500">
                    <li><a href="https://github.com/Hassaan-ahmad-911/freshmarket" class="transition hover:text-brand-700" rel="noopener">Source on GitHub</a></li>
                    <li><a href="https://github.com/Hassaan-ahmad-911/freshmarket/blob/main/docs/IMAGE_CREDITS.md" class="transition hover:text-brand-700" rel="noopener">Photo credits</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-stone-100">
            <div class="container-page flex flex-col gap-2 py-6 text-xs text-stone-400 sm:flex-row sm:items-center sm:justify-between">
                <span>&copy; {{ date('Y') }} FreshMarket · a portfolio project. Payments in Stripe test mode.</span>
                <span>Photos: Wikimedia Commons contributors (CC licences).</span>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
