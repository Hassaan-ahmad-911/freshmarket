@if ($supplierOrder->refunded_amount->isPositive())
    <div class="mt-3 rounded-md bg-sky-50 p-3 space-y-1">
        <p class="text-xs font-semibold uppercase tracking-wide text-sky-800">After refunds</p>
        <div class="flex justify-between text-sky-900"><span>Refunded to customer</span><span>− {{ $supplierOrder->refunded_amount }}</span></div>
        <div class="flex justify-between"><span>Customer paid (net)</span><span>{{ $supplierOrder->netTotal() }}</span></div>
        <div class="flex justify-between text-gray-600"><span>Commission (net)</span><span>− {{ $supplierOrder->netCommission() }}</span></div>
        <div class="flex justify-between font-bold border-t border-sky-200 pt-1"><span>Your payout (net)</span><span>{{ $supplierOrder->payoutDue() }}</span></div>
    </div>
@endif
