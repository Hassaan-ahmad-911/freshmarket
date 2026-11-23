# FreshMarket

A multi-vendor food marketplace built with Laravel. Independent suppliers manage their own
products and orders; customers buy from many suppliers in a single checkout, paid through
Stripe Connect (separate charges and transfers).

> Work in progress — built phase by phase. The full README (architecture + ER diagrams,
> payment/refund flow, Stripe CLI instructions, screenshots) arrives in Phase 7.

## Stack

Laravel 13 · PHP 8.3 · MySQL (SQLite for tests) · Blade + Tailwind + Alpine (Breeze) ·
spatie/laravel-permission · Stripe PHP SDK · Pest

## Architecture (so far)

```
app/Domain/
├── Shared/      Money value object, Role & Permission enums, ModuleServiceProvider base
├── Suppliers/   ┐
├── Catalog/     │ each module: Models, Services, Actions, Events, Listeners, Policies
├── Cart/        │ + its own ServiceProvider (registered in bootstrap/providers.php)
├── Orders/      │
├── Payments/    │ (+ Contracts for the PaymentGateway interface)
└── Payouts/     ┘
```

- Money is always an integer number of cents; percentages are basis points (1000 = 10%).
- Controllers stay thin — business logic lives in module Services/Actions.

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env          # set DB_* for MySQL, or DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed
php artisan storage:link      # makes uploaded logos/product images publicly reachable
composer run dev              # app + queue worker + logs + Vite
```

Emails (invitations, approvals, low-stock alerts) are queued and written to
`storage/logs/laravel.log` (MAIL_MAILER=log). `composer run dev` starts the queue worker.

## Demo logins (password: `password`)

| Role                    | Email                       | Notes                                   |
|-------------------------|-----------------------------|-----------------------------------------|
| Platform Admin          | admin@freshmarket.test      | Approves / suspends suppliers at /admin |
| Supplier Owner          | owner@freshmarket.test      | Green Valley Farm (approved)            |
| Supplier Staff          | staff@freshmarket.test      | Green Valley Farm — products only       |
| Supplier Owner (pending)| pending@freshmarket.test    | Sunrise Bakery, waiting for approval    |
| Customer                | customer@freshmarket.test   |                                         |

## Tests

```bash
./vendor/bin/pest
```
