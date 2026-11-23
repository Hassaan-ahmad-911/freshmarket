<?php

namespace App\Domain\Payments\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Runs `stripe listen` with the right settings for this app:
 *  - authenticated with STRIPE_SECRET from .env (no `stripe login` needed),
 *  - forwarding exactly the events FreshMarket handles,
 *  - to APP_URL/stripe/webhook.
 *
 * The Stripe CLI can't forward classic "snapshot" events and Accounts v2 "thin"
 * events in one session, so the v2 account events (only needed while a real
 * supplier is onboarding) run in a second session: `php artisan stripe:listen --accounts`.
 */
class StripeListen extends Command
{
    protected $signature = 'stripe:listen
        {--accounts : Forward Accounts v2 events (supplier onboarding) instead of payment events}
        {--binary=stripe : Path to the Stripe CLI}';

    protected $description = 'Forward Stripe test-mode webhooks to this app (wraps `stripe listen`)';

    public const PAYMENT_EVENTS = ['payment_intent.succeeded', 'charge.refunded', 'transfer.created', 'account.updated'];

    public const ACCOUNT_EVENTS = ['v2.core.account.updated', 'v2.core.account[configuration.recipient].capability_status_updated'];

    public function handle(): int
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            $this->error('STRIPE_SECRET is empty in .env — nothing to listen to (the app is using the FakeGateway).');

            return self::FAILURE;
        }

        $url = rtrim(config('app.url'), '/').'/stripe/webhook';
        $events = $this->option('accounts') ? self::ACCOUNT_EVENTS : self::PAYMENT_EVENTS;

        $this->info("Forwarding to {$url}: ".implode(', ', $events));

        $process = new Process(
            [$this->option('binary'), 'listen', '--events', implode(',', $events), '--forward-to', $url],
            env: ['STRIPE_API_KEY' => $secret],
        );
        $process->setTimeout(null);

        $warned = false;
        $process->run(function (string $type, string $buffer) use (&$warned) {
            $this->output->write($buffer);

            // The CLI prints the signing secret it uses; it must match STRIPE_WEBHOOK_SECRET.
            if (! $warned && preg_match('/whsec_[A-Za-z0-9]+/', $buffer, $m) && $m[0] !== config('services.stripe.webhook_secret')) {
                $warned = true;
                $this->warn('This signing secret differs from STRIPE_WEBHOOK_SECRET in .env — copy it there, run `php artisan config:clear`, and restart the app.');
            }
        });

        return $process->getExitCode() ?? self::FAILURE;
    }
}
