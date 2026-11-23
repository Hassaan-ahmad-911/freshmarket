@if ($so->refunded_amount->isPositive())
    <div class="flex justify-between text-sky-700"><span>Refunded</span><span>− {{ $so->refunded_amount }}</span></div>
    <div class="flex justify-between font-semibold"><span>{{ $so->supplier->name }} total after refunds</span><span>{{ $so->netTotal() }}</span></div>
@endif
