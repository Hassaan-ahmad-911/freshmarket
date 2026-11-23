@props(['title' => null, 'cartCount' => 0])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50">
<div class="min-h-screen flex flex-col">
    <header x-data="{ open: false }" class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                <x-application-logo class="h-7 w-7 text-emerald-600" />
                <span class="font-bold text-lg tracking-tight">Fresh<span class="text-emerald-600">Market</span></span>
            </a>

            <form action="{{ route('products.index') }}" method="GET" class="hidden md:flex flex-1 max-w-lg">
                <label for="header-search" class="sr-only">Search products</label>
                <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search fresh food from local suppliers…"
                       class="w-full rounded-l-lg border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                <button class="rounded-r-lg bg-emerald-600 px-4 text-white text-sm font-medium hover:bg-emerald-700">Search</button>
            </form>

            <nav class="hidden md:flex items-center gap-5 text-sm font-medium ms-auto">
                <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'text-emerald-700' : 'text-gray-600 hover:text-gray-900' }}">Browse</a>
                <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'text-emerald-700' : 'text-gray-600 hover:text-gray-900' }}">Stores</a>
                @auth
                    @if (Auth::user()->isCustomer())
                        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'text-emerald-700' : 'text-gray-600 hover:text-gray-900' }}">My orders</a>
                        <a href="{{ route('cart.index') }}" class="relative inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 {{ request()->routeIs('cart.*') ? 'text-emerald-700 border-emerald-300' : 'text-gray-700 hover:border-gray-300' }}">
                            🧺 Basket
                            @if ($cartCount > 0)
                                <span class="rounded-full bg-emerald-600 px-1.5 text-xs font-semibold text-white">{{ $cartCount }}</span>
                            @endif
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">{{ Auth::user()->isSupplierUser() ? 'My Store' : 'Admin' }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-gray-500 hover:text-gray-900">Log out</button></form>
                @else
                    <a href="{{ route('supplier.register') }}" class="text-gray-600 hover:text-gray-900">Sell on FreshMarket</a>
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Sign up</a>
                @endauth
            </nav>

            <button @click="open = !open" class="md:hidden ms-auto p-2 text-gray-600" aria-label="Menu">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak class="md:hidden border-t border-gray-200 px-4 py-3 space-y-3 text-sm">
            <form action="{{ route('products.index') }}" method="GET">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products…" class="w-full rounded-lg border-gray-300 text-sm">
            </form>
            <a href="{{ route('products.index') }}" class="block">Browse</a>
            <a href="{{ route('stores.index') }}" class="block">Stores</a>
            @auth
                @if (Auth::user()->isCustomer())
                    <a href="{{ route('cart.index') }}" class="block">Basket ({{ $cartCount }})</a>
                    <a href="{{ route('orders.index') }}" class="block">My orders</a>
                @else
                    <a href="{{ route('dashboard') }}" class="block">My account</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button>Log out</button></form>
            @else
                <a href="{{ route('supplier.register') }}" class="block">Sell on FreshMarket</a>
                <a href="{{ route('login') }}" class="block">Log in</a>
                <a href="{{ route('register') }}" class="block font-medium text-emerald-700">Sign up</a>
            @endauth
        </div>
    </header>

    <x-flash />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-sm text-gray-500 flex flex-col sm:flex-row justify-between gap-2">
            <span>&copy; {{ date('Y') }} FreshMarket — a portfolio project.</span>
            <span>Fresh food from independent local suppliers.</span>
        </div>
    </footer>
</div>
</body>
</html>
