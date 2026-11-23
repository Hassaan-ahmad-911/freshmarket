<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => null])
</head>
<body class="font-sans">
@php
    // One list of links per role — used by the top bar, the mobile menu and the supplier sidebar.
    $user = Auth::user();
    $supplier = $user->supplier;
    $areaLinks = match (true) {
        $user->isPlatformAdmin() => [
            ['Suppliers', route('admin.home'), 'admin.*', 'store'],
        ],
        $user->isSupplierUser() && $supplier !== null => array_values(array_filter([
            ['Dashboard', route('supplier.dashboard'), 'supplier.dashboard', 'chart'],
            ['Orders', route('supplier.orders.index'), 'supplier.orders.*', 'clipboard'],
            ['Products', route('supplier.products.index'), 'supplier.products.*', 'cube'],
            $user->can('viewEarnings', $supplier) ? ['Payouts', route('supplier.payouts.index'), 'supplier.payouts.*', 'banknotes'] : null,
            $user->can('update', $supplier) ? ['Store profile', route('supplier.store.edit'), 'supplier.store.*', 'cog'] : null,
            $user->can('manageStaff', $supplier) ? ['Staff', route('supplier.staff.index'), 'supplier.staff.*', 'users'] : null,
        ])),
        default => [
            ['My Account', route('dashboard'), 'dashboard', 'home'],
        ],
    };
    $hasSidebar = $user->isSupplierUser() && $supplier !== null && request()->routeIs('supplier.*');
@endphp
<div class="min-h-screen animate-fade-in">
    @include('layouts.navigation', ['links' => $areaLinks, 'hasSidebar' => $hasSidebar])
    <x-flash />

    <div class="{{ $hasSidebar ? 'mx-auto flex max-w-[1440px] gap-8 lg:px-8' : '' }}">
        @if ($hasSidebar)
            @include('layouts.partials.supplier-sidebar', ['links' => $areaLinks, 'supplier' => $supplier])
        @endif

        <div class="min-w-0 flex-1">
            @isset($header)
                <header class="{{ $hasSidebar ? 'pt-8' : 'border-b border-stone-200/70 bg-white' }}">
                    <div class="{{ $hasSidebar ? 'px-4 sm:px-6 lg:px-0' : 'container-page py-6' }}">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
</div>
</body>
</html>
