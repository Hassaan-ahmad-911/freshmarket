<?php

namespace App\Domain\Payments\Webhooks;

use App\Domain\Payments\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

/**
 * Store → de-duplicate → handle → record the outcome.
 *
 *  - First delivery of an event: stored and handled.
 *  - Same event again (Stripe retries, or the CLI resends it): already
 *    "processed", so we return straight away. This is webhook idempotency.
 *  - Handler throws: stored as "failed" and the exception bubbles up, so the
 *    controller answers 500 and Stripe retries the delivery later.
 */
class ProcessWebhookEvent
{
    public function __construct(private WebhookHandlerRegistry $handlers) {}

    public function handle(array $event): WebhookEvent
    {
        $record = $this->store($event);

        if ($record->isDone()) {
            return $record;
        }

        $handler = $this->handlers->handlerFor($record->type);

        if ($handler === null) {
            $record->update(['status' => WebhookEvent::IGNORED, 'processed_at' => now()]);

            return $record;
        }

        $record->increment('attempts');

        try {
            $handler->handle($record);
        } catch (Throwable $e) {
            $record->update(['status' => WebhookEvent::FAILED, 'error' => $e->getMessage()]);
            throw $e;
        }

        $record->update(['status' => WebhookEvent::PROCESSED, 'processed_at' => now(), 'error' => null]);

        return $record;
    }

    private function store(array $event): WebhookEvent
    {
        try {
            return WebhookEvent::firstOrCreate(
                ['stripe_event_id' => $event['id']],
                ['type' => $event['type'], 'status' => WebhookEvent::RECEIVED, 'payload' => $event],
            );
        } catch (UniqueConstraintViolationException) {
            // Two deliveries raced; the other one inserted first.
            return WebhookEvent::where('stripe_event_id', $event['id'])->firstOrFail();
        }
    }
}
