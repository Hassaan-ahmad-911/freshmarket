<?php

namespace App\Domain\Payments\Gateways;

final readonly class GatewayRefund
{
    public function __construct(
        public string $id,
        public string $status, // succeeded | pending | failed | ...
    ) {}
}
