{{-- Top bar for signed-in pages. Expects $links ([label, href, routePattern, icon]) and $hasSidebar. --}}
<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-stone-200/80 bg-white/90 backdrop-blur-lg">
    <div class="{{ $hasSidebar ? 'mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-8' : 'container-page' }}">
        <div class="flex h-16 items-center justify-between gap-6">
            <div class="flex items-center gap-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2" aria-label="FreshMarket home">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-glow"><x-application-logo class="h-5 w-5" /></span>
                    <span class="hidden text-lg font-extrabold tracking-tight sm:inline">Fresh<span class="text-brand-600">Market</span></span>
                </a>

                <div class="hidden items-center gap-1 sm:flex {{ $hasSidebar ? 'lg:hidden' : '' }}">
                    @foreach ($links as [$label, $href, $pattern])
                        <a href="{{ $href }}" @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-brand-50 text-brand-700' => request()->routeIs($pattern),
                            'text-stone-600 hover:bg-stone-100 hover:text-ink' => ! request()->routeIs($pattern),
                        ])>{{ $label }}</a>
                    @endforeach
                    <a href="{{ route('products.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-100 hover:text-ink">Shop</a>
                </div>
            </div>

            <!-- Account menu -->
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full border border-stone-200 bg-white py-1 pl-1 pr-3 text-sm font-medium text-stone-700 transition hover:border-stone-300 hover:shadow-soft">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">{{ collect(explode(' ', Auth::user()->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
                            {{ Auth::user()->name }}
                            <x-icon name="chevron-right" class="h-4 w-4 rotate-90 text-stone-400" />
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <button @click="open = ! open" class="rounded-lg p-2 text-stone-500 hover:bg-stone-100 sm:hidden" aria-label="Menu" :aria-expanded="open">
                <x-icon name="menu" class="h-6 w-6" x-show="!open" />
                <x-icon name="x" class="h-6 w-6" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms class="border-t border-stone-200 bg-white sm:hidden">
        <div class="space-y-1 px-4 py-3">
            @foreach ($links as [$label, $href, $pattern, $icon])
                <a href="{{ $href }}" @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium',
                    'bg-brand-50 text-brand-700' => request()->routeIs($pattern),
                    'text-stone-600 hover:bg-stone-100' => ! request()->routeIs($pattern),
                ])><x-icon :name="$icon" class="h-5 w-5" /> {{ $label }}</a>
            @endforeach
            <a href="{{ route('products.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-stone-600 hover:bg-stone-100"><x-icon name="bag" class="h-5 w-5" /> Shop</a>
        </div>
        <div class="border-t border-stone-200 px-4 py-3">
            <p class="px-3 text-sm font-semibold text-ink">{{ Auth::user()->name }}</p>
            <p class="px-3 text-xs text-stone-500">{{ Auth::user()->email }}</p>
            <div class="mt-2 space-y-1">
                <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm text-stone-600 hover:bg-stone-100">{{ __('Profile') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="w-full rounded-xl px-3 py-2.5 text-left text-sm text-stone-600 hover:bg-stone-100">{{ __('Log Out') }}</button>
                </form>
            </div>
        </div>
    </div>
</nav>
