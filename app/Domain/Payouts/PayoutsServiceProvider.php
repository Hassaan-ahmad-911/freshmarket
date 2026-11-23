<?php

namespace App\Domain\Payouts;

use App\Domain\Shared\ModuleServiceProvider;

/**
 * Payouts module: Paying suppliers: Stripe Connect transfers, reversals and earnings.
 *
 * Register this module's policies, event listeners and container
 * bindings here, never in AppServiceProvider.
 */
class PayoutsServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> */
    protected array $listen = [];
}
