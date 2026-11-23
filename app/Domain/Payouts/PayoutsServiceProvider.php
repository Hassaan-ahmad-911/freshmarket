<?php

namespace App\Domain\Payouts;

use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Events\RefundIssued;
use App\Domain\Payments\Webhooks\WebhookHandlerRegistry;
use App\Domain\Payouts\Console\ProvisionDemoAccounts;
use App\Domain\Payouts\Console\RetryTransfers;
use App\Domain\Payouts\Listeners\QueueSupplierTransfers;
use App\Domain\Payouts\Listeners\ReverseSupplierTransfer;
use App\Domain\Payouts\Models\Transfer;
use App\Domain\Payouts\Webhooks\AccountUpdated;
use App\Domain\Payouts\Webhooks\TransferCreated;
use App\Domain\Shared\ModuleServiceProvider;

/**
 * Payouts module: Paying suppliers: Stripe Connect transfers, reversals and earnings.
 */
class PayoutsServiceProvider extends ModuleServiceProvider
{
    protected array $listen = [
        OrderPaid::class => [QueueSupplierTransfers::class],
        RefundIssued::class => [ReverseSupplierTransfer::class],
    ];

    protected function bootModule(): void
    {
        $webhooks = $this->app->make(WebhookHandlerRegistry::class);
        $webhooks->register('account.updated', AccountUpdated::class);
        $webhooks->register('transfer.created', TransferCreated::class);

        // A "dynamic relationship": the Orders module doesn't know transfers exist;
        // this module attaches $supplierOrder->transfer from the outside.
        SupplierOrder::resolveRelationUsing('transfer', fn (SupplierOrder $so) => $so->hasOne(Transfer::class));

        if ($this->app->runningInConsole()) {
            $this->commands([ProvisionDemoAccounts::class, RetryTransfers::class]);
        }
    }
}
