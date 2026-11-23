<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => null])
</head>
<body class="font-sans">
<div class="flex min-h-screen animate-fade-in">
    {{-- Form side --}}
    <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-16 xl:px-24">
        <div class="mx-auto w-full max-w-md">
            <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="FreshMarket home">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white shadow-glow"><x-application-logo class="h-6 w-6" /></span>
                <span class="text-xl font-extrabold tracking-tight">Fresh<span class="text-brand-600">Market</span></span>
            </a>
            <div class="card mt-10 animate-fade-up p-8">
                {{ $slot }}
            </div>
            <p class="mt-6 text-center text-xs text-stone-400">Payments run in Stripe test mode — no real money moves.</p>
        </div>
    </div>

    {{-- Photo side --}}
    <div class="relative hidden overflow-hidden lg:block lg:w-1/2">
        <img src="{{ asset('images/hero.jpg') }}" alt="" class="absolute inset-0 h-full w-full animate-slow-zoom object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10"></div>
        <div class="absolute inset-x-0 bottom-0 p-12 text-white">
            <p class="text-3xl font-extrabold leading-tight tracking-tight">One basket.<br>Many local suppliers.</p>
            <p class="mt-3 max-w-md text-stone-200">Farms, bakeries and dairies near you — paid fairly, delivered fresh.</p>
        </div>
    </div>
</div>
</body>
</html>
