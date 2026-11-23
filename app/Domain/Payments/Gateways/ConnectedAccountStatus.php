<?php

namespace App\Domain\Payments\Gateways;

final readonly class ConnectedAccountStatus
{
    public function __construct(
        public string $id,
        public bool $detailsSubmitted,
        public bool $transfersEnabled,  // capabilities.transfers === "active"
        public bool $payoutsEnabled,
    ) {}

    /** Build from an Accounts v1 object (e.g. the payload of a v1 account.updated webhook). */
    public static function fromStripe(array $account): self
    {
        return new self(
            id: $account['id'],
            detailsSubmitted: (bool) ($account['details_submitted'] ?? false),
            transfersEnabled: ($account['capabilities']['transfers'] ?? null) === 'active',
            payoutsEnabled: (bool) ($account['payouts_enabled'] ?? false),
        );
    }

    /**
     * Build from an Accounts v2 object (retrieved with include=configuration.recipient).
     *
     * In v2, "can receive transfers" is the recipient configuration's
     * stripe_balance.stripe_transfers capability: active | pending | restricted | unsupported.
     */
    public static function fromStripeV2(array $account): self
    {
        $capabilities = $account['configuration']['recipient']['capabilities'] ?? [];
        $transfers = $capabilities['stripe_balance']['stripe_transfers']['status'] ?? null;
        $bankPayouts = collect($capabilities['bank_accounts'] ?? [])->contains(fn ($c) => ($c['status'] ?? null) === 'active');

        return new self(
            id: $account['id'],
            detailsSubmitted: in_array($transfers, ['active', 'pending'], true),
            transfersEnabled: $transfers === 'active',
            payoutsEnabled: $bankPayouts,
        );
    }
}
