<?php

namespace App\Domain\Payments;

use App\Domain\Shared\ModuleServiceProvider;

/**
 * Payments module: Charging the customer: payment gateway, payments, refunds, webhooks.
 *
 * Register this module's policies, event listeners and container
 * bindings here, never in AppServiceProvider.
 */
class PaymentsServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> */
    protected array $listen = [];
}
