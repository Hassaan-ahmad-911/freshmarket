<?php

namespace App\Domain\Payments\Gateways;

use Carbon\CarbonImmutable;

final readonly class CheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
        public CarbonImmutable $expiresAt,
    ) {}
}
