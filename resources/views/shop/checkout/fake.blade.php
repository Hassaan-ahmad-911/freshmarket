<x-shop-layout title="Test payment">
    <div class="mx-auto max-w-md px-4 py-16">
        <div class="card animate-fade-up p-8 text-center">
            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600"><x-icon name="card" class="h-7 w-7" /></span>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-amber-600">Offline test mode — no Stripe keys configured</p>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-ink">Pay {{ $payment->amount }}</h1>
            <p class="mt-1 text-sm text-stone-500">Order {{ $payment->order->number }}</p>
            <p class="mt-4 text-sm text-stone-600">
                This page replaces Stripe Checkout. Clicking "Pay" runs the same code as Stripe's
                <code class="rounded bg-stone-100 px-1 text-xs">payment_intent.succeeded</code> webhook.
            </p>
            <form method="POST" action="{{ route('fake-checkout.pay', $payment) }}" class="mt-6">
                @csrf
                <button class="btn-primary w-full rounded-full py-3.5 text-base">Pay {{ $payment->amount }}</button>
            </form>
            <a href="{{ route('orders.show', ['order' => $payment->order_id, 'checkout' => 'cancelled']) }}" class="mt-3 inline-block text-sm text-stone-500 hover:underline">Cancel</a>
        </div>
    </div>
</x-shop-layout>
