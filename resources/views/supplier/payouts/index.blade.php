<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">Payouts &amp; earnings</h2>
    </x-slot>

    <div class="pb-12 pt-6">
        <div class="px-4 sm:px-6 lg:px-0 space-y-6">
            {{-- Stripe Connect status --}}
            <div class="card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4" data-reveal>
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $supplier->canReceiveTransfers() ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700' }}"><x-icon name="card" class="h-6 w-6" /></span>
                <div>
                    <h3 class="font-semibold text-ink">Stripe account</h3>
                    @if ($supplier->canReceiveTransfers())
                        <p class="mt-1 text-sm text-brand-700">Connected and ready to receive payouts.</p>
                    @elseif ($supplier->stripe_account_id)
                        <p class="mt-1 text-sm text-amber-700">Onboarding not finished — Stripe needs more details before it can send you money.</p>
                    @else
                        <p class="mt-1 text-sm text-stone-600">Connect a Stripe account so FreshMarket can pay you for your orders.</p>
                    @endif
                    @if ($supplier->stripe_account_id)
                        <p class="mt-1 text-xs text-stone-500 font-mono">{{ $supplier->stripe_account_id }}</p>
                        <ul class="mt-2 flex flex-wrap gap-2 text-xs">
                            @foreach (['Details submitted' => $supplier->stripe_details_submitted, 'Transfers enabled' => $supplier->stripe_transfers_enabled] as $check => $ok)
                                <li class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-medium {{ $ok ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700' }}">
                                    <x-icon :name="$ok ? 'check-circle' : 'clock'" class="h-3.5 w-3.5" /> {{ $check }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                </div>
                @unless ($supplier->canReceiveTransfers())
                    <form method="POST" action="{{ route('supplier.payouts.connect') }}">
                        @csrf
                        <button class="btn-primary">
                            {{ $supplier->stripe_account_id ? 'Continue onboarding' : 'Connect with Stripe' }}
                        </button>
                    </form>
                @endunless
            </div>

            {{-- Totals --}}
            <div class="grid grid-cols-2 gap-4 xl:grid-cols-4" data-reveal-stagger>
                @foreach ([
                    ['Gross sales', $totals['gross'], 'What customers paid you, incl. delivery', 'chart', 'bg-sky-50 text-sky-700'],
                    ['Platform commission', $totals['commission'], 'FreshMarket\'s share', 'store', 'bg-stone-100 text-stone-600'],
                    ['Net earnings', $totals['net'], 'Gross − commission', 'banknotes', 'bg-brand-50 text-brand-700'],
                    ['Transferred to you', $totals['transferred']->subtract($totals['reversed']), $totals['reversed']->isPositive() ? "After {$totals['reversed']} of refund reversals" : 'Sent to your Stripe account', 'card', 'bg-violet-50 text-violet-700'],
                ] as [$label, $money, $hint, $icon, $tone])
                    <div class="card p-5 transition duration-300 hover:-translate-y-0.5 hover:shadow-lift" data-reveal>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone }}"><x-icon :name="$icon" /></span>
                        <p class="mt-4 text-sm text-stone-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-extrabold tracking-tight text-ink">{{ $money }}</p>
                        <p class="mt-1 text-xs text-stone-400">{{ $hint }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Transfer history --}}
            <div class="card overflow-x-auto">
                <h3 class="px-5 py-3 border-b border-stone-100 font-semibold">Transfer history</h3>
                <table class="min-w-full text-sm">
                    <thead class="bg-stone-50 text-left text-xs uppercase tracking-wide text-stone-500">
                        <tr><th class="px-5 py-2">Date</th><th class="px-5 py-2">Order</th><th class="px-5 py-2 text-right">Amount</th><th class="px-5 py-2 text-right">Reversed</th><th class="px-5 py-2">Status</th><th class="px-5 py-2">Stripe ID</th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="px-5 py-2 text-stone-600">{{ ($transfer->transferred_at ?? $transfer->created_at)->toFormattedDateString() }}</td>
                                <td class="px-5 py-2"><a href="{{ route('supplier.orders.show', $transfer->supplierOrder) }}" class="text-brand-700 hover:underline">{{ $transfer->supplierOrder->number }}</a></td>
                                <td class="px-5 py-2 text-right">{{ $transfer->amount }}</td>
                                <td class="px-5 py-2 text-right {{ $transfer->reversed_amount->isPositive() ? 'text-red-600' : 'text-stone-400' }}">{{ $transfer->reversed_amount->isPositive() ? '− '.$transfer->reversed_amount : '—' }}</td>
                                <td class="px-5 py-2"><x-status-badge :status="$transfer->status" /></td>
                                <td class="px-5 py-2 font-mono text-xs text-stone-500">{{ $transfer->stripe_transfer_id ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-stone-500">No transfers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-5 py-3">{{ $transfers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
