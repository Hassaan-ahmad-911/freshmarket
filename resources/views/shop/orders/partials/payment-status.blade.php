@php($payment = $order->payment)
@if ($order->status === \App\Domain\Orders\Enums\OrderStatus::PendingPayment)
    @if (request('checkout') === 'success')
        {{-- Back from Stripe, but the webhook hasn't arrived yet. We never trust the redirect alone. --}}
        <div class="mt-4 rounded-lg bg-sky-50 border border-sky-200 p-4 text-sm text-sky-800 flex items-center gap-3">
            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3"/></svg>
            <span>Confirming your payment with Stripe… this page refreshes automatically.</span>
        </div>
        <meta http-equiv="refresh" content="3">
    @else
        <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-3">
            <span>{{ request('checkout') === 'cancelled' ? 'Payment was cancelled.' : 'This order is waiting for payment.' }} Your items are reserved for a short time.</span>
            <form method="POST" action="{{ route('orders.pay', $order) }}">
                @csrf
                <button class="rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-700">Pay {{ $order->total }} now</button>
            </form>
        </div>
    @endif
@elseif ($order->status->isPaid() && $payment)
    <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
        @if (request('checkout') === 'success')<strong>Thank you — payment received!</strong> @endif
        Paid {{ $payment->amount }} on {{ $payment->paid_at?->toDayDateTimeString() }}
        <span class="text-emerald-700/80">· one payment covering all {{ $order->supplierOrders->count() }} suppliers</span>
        @if ($payment->payment_intent_id)
            <span class="block mt-1 text-xs text-emerald-700/80 font-mono">{{ $payment->payment_intent_id }}</span>
        @endif
    </div>
@endif
