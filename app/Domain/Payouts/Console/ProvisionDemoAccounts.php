<?php

namespace App\Domain\Payouts\Console;

use App\Domain\Payouts\Services\DemoConnectedAccounts;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Console\Command;
use Throwable;

class ProvisionDemoAccounts extends Command
{
    protected $signature = 'stripe:demo-accounts {--fresh : Create new accounts (zero balance) instead of reusing the cached ones}';

    protected $description = 'Give every approved supplier a Stripe test connected account that can receive transfers';

    public function handle(): int
    {
        $accounts = DemoConnectedAccounts::make();

        if ($accounts->usesStripe()) {
            $country = $accounts->platformCountry();
            $this->info("Stripe platform account country: {$country}");

            if ($country !== config('services.stripe.connect_country')) {
                $this->warn('Connected accounts will be created in '.config('services.stripe.connect_country').'. Cross-border transfers may be refused — set STRIPE_CONNECT_COUNTRY if needed.');
            }
        }

        foreach (Supplier::approved()->with('owner')->get() as $supplier) {
            try {
                $accounts->provision($supplier, fn (string $line) => $this->line("  {$line}"), fresh: (bool) $this->option('fresh'));
            } catch (Throwable $e) {
                $this->error("  {$supplier->name}: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
