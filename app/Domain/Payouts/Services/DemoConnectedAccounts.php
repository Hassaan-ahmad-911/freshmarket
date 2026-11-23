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
 * platform-managed ("custom") TEST accounts and pre-fill them with Stripe's
 * documented test values (DOB 1901-01-01, address "address_full_match", test
 * bank account), which makes the `transfers` capability active straight away.
 *
 * Without STRIPE_SECRET the suppliers get fake account ids for the FakeGateway.
 * Created account ids are cached in storage/app/private so re-seeding reuses them.
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

    /** The platform account's country, e.g. "US" (null without Stripe). */
    public function platformCountry(): ?string
    {
        return $this->stripe?->accounts->retrieve()->country;
    }

    /** @param  Closure(string): void|null  $log */
    public function provision(Supplier $supplier, ?Closure $log = null): Supplier
    {
        $log ??= fn (string $message) => null;

        if (! $this->usesStripe()) {
            $supplier->update($this->attributes(new ConnectedAccountStatus('acct_fake_'.str_replace('-', '_', $supplier->slug), true, true, true)));
            $log("{$supplier->name}: fake account {$supplier->stripe_account_id} (no STRIPE_SECRET set)");

            return $supplier;
        }

        $accountId = $this->reusableAccount($supplier) ?? $this->createAccount($supplier);
        $status = $this->waitUntilTransfersActive($accountId);

        $supplier->update($this->attributes($status));
        $this->remember($supplier->slug, $accountId);

        $log(sprintf('%s: %s (transfers %s)', $supplier->name, $accountId, $status->transfersEnabled ? 'active' : 'NOT active yet'));

        return $supplier;
    }

    private function createAccount(Supplier $supplier): string
    {
        $email = $supplier->owner->email;

        return $this->stripe->accounts->create([
            'type' => 'custom',
            'country' => config('services.stripe.connect_country', 'US'),
            'email' => $email,
            'business_type' => 'individual',
            'capabilities' => ['transfers' => ['requested' => true]],
            'business_profile' => [
                'name' => $supplier->name,
                'mcc' => '5499', // miscellaneous food stores
                'product_description' => 'Fresh food sold through the FreshMarket marketplace.',
            ],
            'individual' => [
                'first_name' => strtok($supplier->owner->name, ' ') ?: 'Demo',
                'last_name' => 'Supplier',
                'email' => $email,
                'phone' => '0000000000',
                'dob' => ['day' => 1, 'month' => 1, 'year' => 1901],          // test value: verified
                'ssn_last_4' => '0000',
                'id_number' => '000000000',
                'address' => [
                    'line1' => 'address_full_match',                          // test value: verified
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'postal_code' => '94111',
                    'country' => 'US',
                ],
            ],
            'external_account' => [
                'object' => 'bank_account',
                'country' => 'US',
                'currency' => 'usd',
                'routing_number' => '110000000',                               // Stripe test bank
                'account_number' => '000123456789',
            ],
            // A real platform records the supplier's own IP when they accept the terms;
            // a seeder has none, so a public placeholder address is used for the test account.
            'tos_acceptance' => ['date' => time(), 'ip' => '8.8.8.8'],
            // Manual payouts: transferred money stays visible in the account's balance for the demo.
            'settings' => ['payouts' => ['schedule' => ['interval' => 'manual']]],
            'metadata' => ['supplier_slug' => $supplier->slug, 'freshmarket_demo' => 'true'],
        ])->id;
    }

    private function waitUntilTransfersActive(string $accountId, int $attempts = 10): ConnectedAccountStatus
    {
        for ($i = 1; $i <= $attempts; $i++) {
            $status = ConnectedAccountStatus::fromStripe($this->stripe->accounts->retrieve($accountId)->toArray());

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
            $this->stripe->accounts->retrieve($accountId);

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
