<?php

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Every Stripe event we received. The UNIQUE stripe_event_id column is what
 * makes webhooks idempotent: Stripe may deliver the same event twice (or more),
 * but only the first delivery is processed.
 */
#[Fillable(['stripe_event_id', 'type', 'status', 'payload', 'attempts', 'error', 'processed_at'])]
class WebhookEvent extends Model
{
    public const RECEIVED = 'received';

    public const PROCESSED = 'processed';

    public const IGNORED = 'ignored';

    public const FAILED = 'failed';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function isDone(): bool
    {
        return in_array($this->status, [self::PROCESSED, self::IGNORED], true);
    }

    /** The Stripe object the event is about (a PaymentIntent, a Charge, an Account...). */
    public function object(): array
    {
        return $this->payload['data']['object'] ?? [];
    }
}
