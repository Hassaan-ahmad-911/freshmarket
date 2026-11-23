@if ($order->refunded_total->isPositive())
    <div class="flex justify-between text-sky-700"><span>Refunded to your card</span><span>− {{ $order->refunded_total }}</span></div>
    <div class="flex justify-between font-bold text-ink"><span>Net paid</span><span>{{ $order->total->subtract($order->refunded_total) }}</span></div>
@endif
