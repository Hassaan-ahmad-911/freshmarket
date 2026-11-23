<?php

namespace App\Domain\Suppliers;

use App\Domain\Shared\ModuleServiceProvider;

/**
 * Suppliers module: Supplier stores, approval, staff and Stripe Connect onboarding.
 *
 * Register this module's policies, event listeners and container
 * bindings here, never in AppServiceProvider.
 */
class SuppliersServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> */
    protected array $listen = [];
}
