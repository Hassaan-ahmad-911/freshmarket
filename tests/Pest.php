<?php

use App\Domain\Orders\Actions\PlaceOrder;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\DeliveryDetails;
use App\Domain\Payments\Actions\MarkPaymentSucceeded;
use App\Domain\Payments\Actions\StartCheckout;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot Laravel and get a fresh in-memory SQLite database
| (with roles seeded — see tests/TestCase.php) for every test.
|
| Unit tests boot Laravel too (so helpers like config() work) but never
| touch the database.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** The owner of a brand-new (approved, unless told otherwise) store. */
function supplierOwner(?Supplier $supplier = null): User
{
    return ($supplier ?? Supplier::factory()->create())->owner->refresh();
}

/**
 * Place an order directly through the PlaceOrder action.
 *
 * @param  array<int, int>  $lines  product_id => quantity
 */
function placeOrder(array $lines, ?User $customer = null, string $area = 'Camden'): Order
{
    return app(PlaceOrder::class)->handle(
        $customer ?? User::factory()->customer()->create(),
        $lines,
        new DeliveryDetails('Casey Customer', '555-0100', '1 High Street', $area),
    );
}

/** A store that delivers to Camden with the given commission (basis points). */
function camdenStore(?int $commissionBps = null, int $minOrder = 0): Supplier
{
    return Supplier::factory()->deliveringTo(['Camden'])->create([
        'commission_bps' => $commissionBps,
        'min_order_amount' => $minOrder,
    ]);
}

/** A Camden store with a (fake) Stripe connected account that can receive transfers. */
function connectedStore(?int $commissionBps = null): Supplier
{
    $store = camdenStore($commissionBps);
    $store->update(['stripe_account_id' => 'acct_test_'.$store->id, 'stripe_details_submitted' => true, 'stripe_transfers_enabled' => true, 'stripe_payouts_enabled' => true]);

    return $store;
}

/** The FakeGateway the app is using in tests (records every call). */
function fakeGateway(): FakeGateway
{
    return app(PaymentGateway::class);
}

/** Place an order and confirm its payment, exactly like the webhook would. */
function paidOrder(array $lines, ?User $customer = null): Order
{
    $order = placeOrder($lines, $customer);
    app(StartCheckout::class)->handle($order);
    app(MarkPaymentSucceeded::class)->handle($order->id, 'pi_test_'.$order->id, 'ch_test_'.$order->id, $order->total->amount);

    return $order->fresh(['supplierOrders.items', 'supplierOrders.transfer', 'payment']);
}

/** Build a Stripe event payload. */
function stripeEvent(string $type, array $object, ?string $id = null): array
{
    return [
        'id' => $id ?? 'evt_'.Str::random(20),
        'object' => 'event',
        'type' => $type,
        'created' => time(),
        'livemode' => false,
        'data' => ['object' => $object],
    ];
}

/** POST a webhook signed exactly the way Stripe signs it (HMAC-SHA256 of "timestamp.payload"). */
function postStripeWebhook(array $event, ?string $secret = null): TestResponse
{
    $secret ??= config('services.stripe.webhook_secret');
    $payload = json_encode($event);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

/** A staff member working for the given store. */
function supplierStaff(Supplier $supplier): User
{
    $staff = User::factory()->supplierStaff()->create();
    $staff->forceFill(['supplier_id' => $supplier->id])->save();

    return $staff;
}
