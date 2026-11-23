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

    /** Build from a Stripe Account object or an account.updated webhook payload. */
    public static function fromStripe(array $account): self
    {
        return new self(
            id: $account['id'],
            detailsSubmitted: (bool) ($account['details_submitted'] ?? false),
            transfersEnabled: ($account['capabilities']['transfers'] ?? null) === 'active',
            payoutsEnabled: (bool) ($account['payouts_enabled'] ?? false),
        );
    }
}
