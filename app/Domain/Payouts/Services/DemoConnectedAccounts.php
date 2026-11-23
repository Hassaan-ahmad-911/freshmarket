<?php

namespace App\Domain\Payouts\Services;

use App\Domain\Payments\Gateways\ConnectedAccountStatus;
use App\Domain\Suppliers\Models\Supplier;
use Closure;
use Illuminate\Support\Facades\Storage;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Gives the DEMO suppliers a connected account that can receive transfers immediately.
 *
 * Why not Express here? An Express account only becomes active after a person
 * clicks through Stripe's hosted onboarding. That's what real suppliers do
 * (see StartConnectOnboarding), but a seeder can't click. So for the demo we create
 * Accounts v2 "recipient" TEST accounts with no dashboard, where the platform
 * provides the details via the API, pre-filled with Stripe's documented sandbox
 * values (DOB 1901-01-01, address "address_full_match", SSN 000000000). The
 * stripe_balance.stripe_transfers capability then verifies within seconds.
 *
 * Without STRIPE_SECRET the suppliers get fake account ids for the FakeGateway.
 * Created account ids are cached in storage/app/private so re-seeding reuses them
 * (pass $fresh = true to create new ones, e.g. to start a recording at a zero balance).
 */
class DemoConnectedAccounts
{
    private const CACHE_FILE = 'stripe-demo-accounts.json';

    public function __construct(private ?StripeClient $stripe) {}

    public static function make(): self
    {
        $secret = config('services.stripe.secret');

        return new self($secret ? new StripeClient(['api_key' => $secret, 'max_network_retries' => 2]) : null);
    }

    public function usesStripe(): bool
    {
        return $this->stripe !== null;
    }

    /** The platform account's country, e.g. "US" (null without Stripe, or if it can't be read). */
    public function platformCountry(): ?string
    {
        try {
            return $this->stripe?->accounts->retrieve()->country;
        } catch (ApiErrorException) {
            return null;
        }
    }

    /** @param  Closure(string): void|null  $log */
    public function provision(Supplier $supplier, ?Closure $log = null, bool $fresh = false): Supplier
    {
        $log ??= fn (string $message) => null;

        if (! $this->usesStripe()) {
            $supplier->update($this->attributes(new ConnectedAccountStatus('acct_fake_'.str_replace('-', '_', $supplier->slug), true, true, true)));
            $log("{$supplier->name}: fake account {$supplier->stripe_account_id} (no STRIPE_SECRET set)");

            return $supplier;
        }

        $accountId = ($fresh ? null : $this->reusableAccount($supplier)) ?? $this->createAccount($supplier);
        $status = $this->waitUntilTransfersActive($accountId);

        $supplier->update($this->attributes($status));
        $this->remember($supplier->slug, $accountId);

        $log(sprintf('%s: %s (transfers %s)', $supplier->name, $accountId, $status->transfersEnabled ? 'active' : 'NOT active yet'));

        if (! $status->transfersEnabled) {
            foreach ($this->outstandingRequirements($accountId) as $requirement) {
                $log("    still required: {$requirement}");
            }
        }

        return $supplier;
    }

    /**
     * An Accounts v2 "recipient" account with no dashboard, whose details the
     * platform provides via the API — pre-filled with Stripe's sandbox test
     * values (DOB 1901-01-01, address_full_match, SSN 000000000) so the
     * stripe_transfers capability can verify without a human onboarding.
     */
    private function createAccount(Supplier $supplier): string
    {
        $email = $supplier->owner->email;
        $country = config('services.stripe.connect_country', 'US');

        return $this->stripe->v2->core->accounts->create([
            'contact_email' => $email,
            'display_name' => $supplier->name,
            'dashboard' => 'none',
            'identity' => [
                'country' => $country,
                'entity_type' => 'individual',
                'individual' => [
                    'given_name' => strtok($supplier->owner->name, ' ') ?: 'Demo',
                    'surname' => 'Supplier',
                    'email' => $email,
                    'phone' => '+18888675309',                                       // Stripe's test phone
                    'date_of_birth' => ['day' => 1, 'month' => 1, 'year' => 1901],   // test value: verifies
                    'address' => [
                        'line1' => 'address_full_match',                            // test value: verifies
                        'city' => 'San Francisco',
                        'state' => 'CA',
                        'postal_code' => '94111',
                        'country' => $country,
                    ],
                    'id_numbers' => [['type' => 'us_ssn', 'value' => '000000000']],   // test SSN
                ],
                // A real platform records the supplier's own IP when they accept the terms;
                // a seeder has none, so a public placeholder address is used for the test account.
                'attestations' => [
                    'terms_of_service' => ['account' => ['date' => now()->utc()->format('Y-m-d\TH:i:s.v\Z'), 'ip' => '8.8.8.8']],
                ],
            ],
            'configuration' => [
                'recipient' => ['capabilities' => ['stripe_balance' => ['stripe_transfers' => ['requested' => true]]]],
            ],
            'defaults' => [
                'currency' => strtolower(config('freshmarket.currency', 'usd')),
                'profile' => [
                    'business_url' => 'https://accessible.stripe.com', // Stripe's test URL that always validates
                    'product_description' => 'Fresh food sold through the FreshMarket marketplace.',
                ],
                'responsibilities' => ['fees_collector' => 'application', 'losses_collector' => 'application'],
            ],
            'metadata' => ['supplier_slug' => $supplier->slug, 'freshmarket_demo' => 'true'],
        ])->id;
    }

    private function retrieveStatus(string $accountId): ConnectedAccountStatus
    {
        $account = $this->stripe->v2->core->accounts->retrieve($accountId, ['include' => ['configuration.recipient', 'requirements']]);

        return ConnectedAccountStatus::fromStripeV2($account->toArray());
    }

    /** The requirements Stripe still wants (empty when verified) — printed when an account doesn't activate. */
    public function outstandingRequirements(string $accountId): array
    {
        $account = $this->stripe->v2->core->accounts->retrieve($accountId, ['include' => ['requirements']])->toArray();

        return collect($account['requirements']['entries'] ?? [])
            ->map(fn (array $entry) => ($entry['description'] ?? '?').' ['.implode(',', array_column($entry['errors'] ?? [], 'code')).']')
            ->all();
    }

    private function waitUntilTransfersActive(string $accountId, int $attempts = 15): ConnectedAccountStatus
    {
        for ($i = 1; $i <= $attempts; $i++) {
            $status = $this->retrieveStatus($accountId);

            if ($status->transfersEnabled || $i === $attempts) {
                return $status;
            }

            sleep(2); // verification of test data is asynchronous, usually a few seconds
        }

        return $status;
    }

    private function reusableAccount(Supplier $supplier): ?string
    {
        $accountId = $supplier->stripe_account_id ?? ($this->cache()[$supplier->slug] ?? null);

        if (! $accountId || str_starts_with($accountId, 'acct_fake_')) {
            return null;
        }

        try {
            $this->stripe->v2->core->accounts->retrieve($accountId);

            return $accountId;
        } catch (ApiErrorException) {
            return null; // deleted, or belongs to a different Stripe account
        }
    }

    private function attributes(ConnectedAccountStatus $status): array
    {
        return [
            'stripe_account_id' => $status->id,
            'stripe_details_submitted' => $status->detailsSubmitted,
            'stripe_transfers_enabled' => $status->transfersEnabled,
            'stripe_payouts_enabled' => $status->payoutsEnabled,
            'stripe_synced_at' => now(),
        ];
    }

    private function cache(): array
    {
        return json_decode(Storage::disk('local')->get(self::CACHE_FILE) ?? '{}', true) ?: [];
    }

    private function remember(string $slug, string $accountId): void
    {
        Storage::disk('local')->put(self::CACHE_FILE, json_encode([$slug => $accountId] + $this->cache(), JSON_PRETTY_PRINT));
    }
}
