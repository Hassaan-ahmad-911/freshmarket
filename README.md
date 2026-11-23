# FreshMarket

A multi-vendor food marketplace built with Laravel. Independent suppliers manage their own
products and orders. Customers buy from many suppliers in a single checkout, paid through
Stripe Connect using **separate charges and transfers**.

> Work in progress, built phase by phase. Phases 1–5 are done (catalog, cart, checkout,
> order splitting, Stripe Connect payments, transfers, webhooks, partial refunds).
> The Filament admin panel, full diagrams and screenshots arrive in Phases 6–7.

## Stack

Laravel 13 · PHP 8.3 · MySQL (SQLite for tests) · Blade + Tailwind + Alpine (Breeze) ·
spatie/laravel-permission · Stripe PHP SDK + Stripe Connect · Pest

## Architecture

```
app/Domain/
├── Shared/      Money value object, Role & Permission enums, ModuleServiceProvider base
├── Suppliers/   stores, approval, staff invitations
├── Catalog/     products, categories, images, atomic stock
├── Cart/        one basket, items from many suppliers
├── Orders/      Order → SupplierOrder (one per supplier) → OrderItem, status state machine
├── Payments/    PaymentGateway (Stripe | Fake), checkout, refunds, webhook endpoint + registry
└── Payouts/     per-supplier transfers, reversals, Connect onboarding, earnings
```

- Money is always an integer number of cents; percentages are basis points (1000 = 10%).
- Controllers stay thin; business logic lives in module Actions/Services.
- Each module registers its own policies, listeners and webhook handlers in its ServiceProvider.

## How the money moves

1. **Checkout** creates one `Order` and one `SupplierOrder` per supplier. Per line it
   stores the price, commission (per supplier rate, rounded once per line) and delivery fee.
   Stock is reserved in the same transaction.
2. **Payment**: one Stripe Checkout Session on the *platform* account for the whole order,
   tagged `transfer_group = order_{id}`.
3. **`payment_intent.succeeded` webhook** (signature verified, stored once in
   `webhook_events`) marks the order paid. The redirect back from Stripe never does this.
4. **Transfers** (queued): one Stripe Transfer per supplier order, for
   `total − commission`, to the supplier's connected account, with
   `source_transaction = charge` and an idempotency key.
5. **Partial refund** of an item (or cancelling a supplier order):
   - the customer is refunded `unit price × quantity` (Stripe Refund);
   - the platform gives up its commission on those units;
   - only *that* supplier's transfer is reversed by the rest (Stripe Transfer Reversal).

   Other suppliers in the same order are not touched.

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env          # set DB_* for MySQL, or DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer run dev              # web server + queue worker + logs
```

Without Stripe keys the app uses the built-in **FakeGateway**: a local "test payment" page
stands in for Stripe Checkout, and transfers/refunds get fake IDs. Everything else works the same.

### Stripe (test mode) + Stripe CLI

1. In the Stripe Dashboard (test mode): **Connect → Get started**, then complete
   **Settings → Connect → Platform profile**.
2. Put your test keys in `.env`: `STRIPE_KEY=pk_test_…`, `STRIPE_SECRET=sk_test_…`.
3. Forward webhooks to your machine and copy the printed `whsec_…` into `STRIPE_WEBHOOK_SECRET`:
   ```bash
   stripe login
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```
4. `php artisan config:clear && php artisan migrate:fresh --seed`. The seeder creates a
   verified Stripe **test** connected account for each approved demo supplier
   (or run `php artisan stripe:demo-accounts`).
5. Pay with card `4242 4242 4242 4242`, any future date, any CVC.

Useful commands: `php artisan payouts:retry` (retry blocked/failed transfers and reversals),
`php artisan orders:stripe-links FM-000001` (Dashboard links for an order).

The client demo walkthrough is in [docs/demo-script.md](docs/demo-script.md).

## Demo logins (password: `password`)

| Role                    | Email                       | Notes                                        |
|-------------------------|-----------------------------|----------------------------------------------|
| Platform Admin          | admin@freshmarket.test      | Approves / suspends suppliers at /admin      |
| Customer                | customer@freshmarket.test   |                                              |
| Supplier Owner          | owner@freshmarket.test      | Green Valley Farm (approved, 10% commission) |
| Supplier Staff          | staff@freshmarket.test      | Green Valley Farm (products & orders only)   |
| Supplier Owner          | baker@freshmarket.test      | Sunrise Bakery (approved, 12% commission)    |
| Supplier Owner (pending)| pending@freshmarket.test    | Hilltop Dairy, waiting for approval          |

## Tests

```bash
./vendor/bin/pest
```

Tests always use the FakeGateway (forced in `phpunit.xml`), even when `.env` has real keys.
