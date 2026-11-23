@php($transfer = $supplierOrder->transfer)
<div class="bg-white shadow-sm sm:rounded-lg p-5 text-sm">
    <h3 class="font-semibold">Payout to your Stripe account</h3>
    @if ($transfer)
        <dl class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div><dt class="text-gray-500">Status</dt><dd class="mt-1"><x-status-badge :status="$transfer->status" /></dd></div>
            <div><dt class="text-gray-500">Transferred</dt><dd class="mt-1 font-medium">{{ $transfer->amount }}</dd></div>
            <div><dt class="text-gray-500">Reversed</dt><dd class="mt-1 font-medium">{{ $transfer->reversed_amount }}</dd></div>
            <div><dt class="text-gray-500">You keep</dt><dd class="mt-1 font-bold">{{ $transfer->netAmount() }}</dd></div>
        </dl>
        @if ($transfer->stripe_transfer_id)
            <p class="mt-3 text-xs text-gray-500">Stripe transfer <span class="font-mono">{{ $transfer->stripe_transfer_id }}</span> → <span class="font-mono">{{ $transfer->destination_account }}</span></p>
        @endif
        @if ($transfer->failure_message)
            <p class="mt-2 text-xs text-red-600">{{ $transfer->failure_message }}</p>
        @endif
    @else
        <p class="mt-2 text-gray-500">The transfer is created automatically a few seconds after the customer's payment is confirmed.</p>
    @endif
</div>
