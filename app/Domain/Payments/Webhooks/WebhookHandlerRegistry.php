<?php

namespace App\Domain\Payments\Webhooks;

use Illuminate\Contracts\Container\Container;

/**
 * Maps Stripe event types to handler classes.
 *
 * Each module registers the events IT cares about in its own service provider
 * (Payments: payment_intent.succeeded, Payouts: account.updated + transfer.created,
 * refunds: charge.refunded). Adding an event never means editing the webhook code.
 */
class WebhookHandlerRegistry
{
    /** @var array<string, class-string<WebhookHandler>> */
    private array $handlers = [];

    public function __construct(private Container $container) {}

    /** @param  class-string<WebhookHandler>  $handlerClass */
    public function register(string $eventType, string $handlerClass): void
    {
        $this->handlers[$eventType] = $handlerClass;
    }

    public function handlerFor(string $eventType): ?WebhookHandler
    {
        $class = $this->handlers[$eventType] ?? null;

        return $class ? $this->container->make($class) : null;
    }

    /** @return list<string> */
    public function eventTypes(): array
    {
        return array_keys($this->handlers);
    }
}
