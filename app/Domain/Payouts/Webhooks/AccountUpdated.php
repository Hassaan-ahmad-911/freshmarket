<?php

namespace App\Domain\Payouts\Webhooks;

use App\Domain\Payments\Gateways\ConnectedAccountStatus;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\Webhooks\WebhookHandler;
use App\Domain\Payouts\Actions\SyncConnectedAccount;
use App\Domain\Suppliers\Models\Supplier;

/** account.updated → a supplier finished (or lost) Stripe onboarding. */
class AccountUpdated implements WebhookHandler
{
    public function __construct(private SyncConnectedAccount $sync) {}

    public function handle(WebhookEvent $event): void
    {
        $account = $event->object();
        $supplier = Supplier::where('stripe_account_id', $account['id'] ?? null)->first();

        if ($supplier) {
            $this->sync->handle($supplier, ConnectedAccountStatus::fromStripe($account));
        }
    }
}
