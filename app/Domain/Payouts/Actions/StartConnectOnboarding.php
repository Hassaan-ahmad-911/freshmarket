<?php

namespace App\Domain\Payouts\Actions;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Suppliers\Models\Supplier;

/**
 * Stripe Connect Express onboarding:
 *  1. create the connected account once (acct_...),
 *  2. ask Stripe for a one-time onboarding link,
 *  3. the supplier fills in their details on Stripe's own pages,
 *  4. Stripe sends them back to our return URL (and fires account.updated).
 */
class StartConnectOnboarding
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Supplier $supplier): string
    {
        if ($supplier->stripe_account_id === null) {
            $accountId = $this->gateway->createConnectedAccount(
                email: $supplier->owner->email,
                businessName: $supplier->name,
                metadata: ['supplier_id' => (string) $supplier->id],
            );
            $supplier->update(['stripe_account_id' => $accountId]);
        }

        return $this->gateway->createOnboardingLink(
            $supplier->stripe_account_id,
            refreshUrl: route('supplier.payouts.refresh'),
            returnUrl: route('supplier.payouts.return'),
        );
    }
}
