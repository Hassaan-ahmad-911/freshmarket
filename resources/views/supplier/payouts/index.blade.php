<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payouts &amp; earnings</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Stripe Connect status --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold">Stripe account</h3>
                    @if ($supplier->canReceiveTransfers())
                        <p class="mt-1 text-sm text-emerald-700">Connected and ready to receive payouts.</p>
                    @elseif ($supplier->stripe_account_id)
                        <p class="mt-1 text-sm text-amber-700">Onboarding not finished — Stripe needs more details before it can send you money.</p>
                    @else
                        <p class="mt-1 text-sm text-gray-600">Connect a Stripe account so FreshMarket can pay you for your orders.</p>
                    @endif
                    @if ($supplier->stripe_account_id)
                        <p class="mt-1 text-xs text-gray-500 font-mono">{{ $supplier->stripe_account_id }}</p>
                        <ul class="mt-2 flex flex-wrap gap-3 text-xs text-gray-600">
                            <li>{{ $supplier->stripe_details_submitted ? '✅' : '⏳' }} Details submitted</li>
                            <li>{{ $supplier->stripe_transfers_enabled ? '✅' : '⏳' }} Transfers enabled</li>
                            <li>{{ $supplier->stripe_payouts_enabled ? '✅' : '⏳' }} Bank payouts enabled</li>
                        </ul>
                    @endif
                </div>
                @unless ($supplier->canReceiveTransfers())
                    <form method="POST" action="{{ route('supplier.payouts.connect') }}">
                        @csrf
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            {{ $supplier->stripe_account_id ? 'Continue onboarding' : 'Connect with Stripe' }}
                        </button>
                    </form>
                @endunless
            </div>

            {{-- Totals --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    ['Gross sales', $totals['gross'], 'What customers paid you, incl. delivery'],
                    ['Platform commission', $totals['commission'], 'FreshMarket\'s share'],
                    ['Net earnings', $totals['net'], 'Gross − commission'],
                    ['Transferred to you', $totals['transferred']->subtract($totals['reversed']), $totals['reversed']->isPositive() ? "After {$totals['reversed']} of refund reversals" : 'Sent to your Stripe account'],
                ] as [$label, $money, $hint])
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ $money }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Transfer history --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <h3 class="px-5 py-3 border-b border-gray-100 font-semibold">Transfer history</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr><th class="px-5 py-2">Date</th><th class="px-5 py-2">Order</th><th class="px-5 py-2 text-right">Amount</th><th class="px-5 py-2 text-right">Reversed</th><th class="px-5 py-2">Status</th><th class="px-5 py-2">Stripe ID</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="px-5 py-2 text-gray-600">{{ ($transfer->transferred_at ?? $transfer->created_at)->toFormattedDateString() }}</td>
                                <td class="px-5 py-2"><a href="{{ route('supplier.orders.show', $transfer->supplierOrder) }}" class="text-emerald-700 hover:underline">{{ $transfer->supplierOrder->number }}</a></td>
                                <td class="px-5 py-2 text-right">{{ $transfer->amount }}</td>
                                <td class="px-5 py-2 text-right {{ $transfer->reversed_amount->isPositive() ? 'text-red-600' : 'text-gray-400' }}">{{ $transfer->reversed_amount->isPositive() ? '− '.$transfer->reversed_amount : '—' }}</td>
                                <td class="px-5 py-2"><x-status-badge :status="$transfer->status" /></td>
                                <td class="px-5 py-2 font-mono text-xs text-gray-500">{{ $transfer->stripe_transfer_id ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-gray-500">No transfers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3">{{ $transfers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
