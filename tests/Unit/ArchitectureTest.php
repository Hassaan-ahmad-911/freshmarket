<?php

/*
| Architecture tests read the source code (they don't run it) and fail the
| build if someone breaks a structural rule — e.g. putting HTTP code inside
| the domain layer. They keep the modular design honest as the app grows.
*/

arch('the domain layer does not depend on the HTTP layer')
    ->expect('App\Domain')
    ->not->toUse('App\Http');

arch('every module has a provider that extends the shared base provider')
    ->expect(array_map(
        fn (string $module) => "App\\Domain\\{$module}\\{$module}ServiceProvider",
        ['Catalog', 'Cart', 'Orders', 'Payments', 'Payouts', 'Suppliers'],
    ))
    ->toExtend('App\Domain\Shared\ModuleServiceProvider');

arch('the Money value object is final and immutable')
    ->expect('App\Domain\Shared\Money\Money')
    ->toBeFinal()
    ->toBeReadonly();

arch('no debugging helpers are left in the code')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
