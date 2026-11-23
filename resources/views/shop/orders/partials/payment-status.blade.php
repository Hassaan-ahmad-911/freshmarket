@php($payment = $order->payment)
@if ($order->status === \App\Domain\Orders\Enums\OrderStatus::PendingPayment)
    @if (request('checkout') === 'success')
        {{-- Back from Stripe, but the webhook hasn't arrived yet. We never trust the redirect alone. --}}
        <div class="mt-6 flex items-center gap-4 rounded-2xl border border-sky-200 bg-sky-50 p-5 text-sm text-sky-800 animate-fade-up">
            <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3"/></svg>
            <div>
                <p class="font-semibold">Confirming your payment with Stripe…</p>
                <p class="text-sky-700">This page refreshes automatically.</p>
            </div>
        </div>
        <meta http-equiv="refresh" content="3">
    @else
        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 animate-fade-up">
            <span class="flex items-center gap-2"><x-icon name="clock" /> {{ request('checkout') === 'cancelled' ? 'Payment was cancelled.' : 'This order is waiting for payment.' }} Your items are reserved for a short time.</span>
            <form method="POST" action="{{ route('orders.pay', $order) }}">
                @csrf
                <button class="btn-primary rounded-full">Pay {{ $order->total }} now</button>
            </form>
        </div>
    @endif
@elseif ($order->status->isPaid() && $payment)
    <div class="mt-6 flex items-start gap-4 rounded-2xl border border-brand-200 bg-brand-50 p-5 text-sm text-brand-800 animate-fade-up">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white shadow-glow"><x-icon name="check-circle" /></span>
        <div>
            @if (request('checkout') === 'success')<p class="font-semibold">Thank you — payment received!</p>@endif
            <p>Paid {{ $payment->amount }} on {{ $payment->paid_at?->toDayDateTimeString() }}
                <span class="text-brand-700/80">· one payment covering all {{ $order->supplierOrders->count() }} suppliers</span></p>
            @if ($payment->payment_intent_id)
                <p class="mt-1 font-mono text-xs text-brand-700/70">{{ $payment->payment_intent_id }}</p>
            @endif
        </div>
    </div>
@endif
