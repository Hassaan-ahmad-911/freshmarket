@php($transfer = $supplierOrder->transfer)
<div class="bg-white shadow-sm sm:rounded-lg p-5 text-sm">
    <h3 class="font-semibold">Payout to your Stripe account</h3>
    @if ($transfer)
        <dl class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div><dt class="text-gray-500">Status</dt><dd class="mt-1"><x-status-badge :status="$transfer->status" /></dd></div>
            <div><dt class="text-gray-500">Transferred</dt><dd class="mt-1 font-medium">{{ $transfer->amount }}</dd></div>
            <div><dt class="text-gray-500">Reversed</dt><dd class="mt-1 font-medium {{ $transfer->reversed_amount->isPositive() ? 'text-red-600' : '' }}">{{ $transfer->reversed_amount->isPositive() ? '− '.$transfer->reversed_amount : $transfer->reversed_amount }}</dd></div>
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

@if ($supplierOrder->refunds->isNotEmpty())
    <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
        <h3 class="px-5 py-3 border-b border-gray-100 font-semibold">Refunds</h3>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-2">When</th>
                    <th class="px-5 py-2">What</th>
                    <th class="px-5 py-2 text-right">To customer</th>
                    <th class="px-5 py-2 text-right">From you</th>
                    <th class="px-5 py-2 text-right">Commission returned</th>
                    <th class="px-5 py-2">Status</th>
                    <th class="px-5 py-2">Stripe IDs</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($supplierOrder->refunds as $refund)
                    <tr>
                        <td class="px-5 py-2 text-gray-600 whitespace-nowrap">{{ $refund->created_at->format('M j, H:i') }}</td>
                        <td class="px-5 py-2">{{ $refund->description() }}@if ($refund->reason)<br><span class="text-xs text-gray-500">{{ $refund->reason }}</span>@endif</td>
                        <td class="px-5 py-2 text-right font-medium">{{ $refund->amount }}</td>
                        <td class="px-5 py-2 text-right text-red-600">{{ $refund->reversal_amount->isPositive() ? '− '.$refund->reversal_amount : '—' }}</td>
                        <td class="px-5 py-2 text-right text-gray-600">{{ $refund->commission_amount }}</td>
                        <td class="px-5 py-2"><x-status-badge :status="$refund->status" /></td>
                        <td class="px-5 py-2 font-mono text-xs text-gray-500">
                            {{ $refund->stripe_refund_id ?? '—' }}
                            @if ($refund->stripe_reversal_id)<br>{{ $refund->stripe_reversal_id }}@endif
                            @if ($refund->reversal_status === 'failed')<br><span class="text-red-600 font-sans">Reversal failed — will be retried</span>@endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
