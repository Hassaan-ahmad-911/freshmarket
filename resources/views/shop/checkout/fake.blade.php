<x-shop-layout title="Test payment">
    <div class="max-w-md mx-auto px-4 py-16">
        <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Offline test mode — no Stripe keys configured</p>
            <h1 class="mt-3 text-2xl font-bold">Pay {{ $payment->amount }}</h1>
            <p class="mt-1 text-sm text-gray-500">Order {{ $payment->order->number }}</p>
            <p class="mt-4 text-sm text-gray-600">
                This page replaces Stripe Checkout. Clicking "Pay" runs the same code as Stripe's
                <code class="text-xs">payment_intent.succeeded</code> webhook.
            </p>
            <form method="POST" action="{{ route('fake-checkout.pay', $payment) }}" class="mt-6">
                @csrf
                <button class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-semibold text-white hover:bg-emerald-700">Pay {{ $payment->amount }}</button>
            </form>
            <a href="{{ route('orders.show', ['order' => $payment->order_id, 'checkout' => 'cancelled']) }}" class="mt-3 inline-block text-sm text-gray-500 hover:underline">Cancel</a>
        </div>
    </div>
</x-shop-layout>
