<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">Orders</h2>
        <p class="text-sm text-stone-500">Only your store's part of each order is shown here.</p>
    </x-slot>

    <div class="pb-12 pt-6">
        <div class="px-4 sm:px-6 lg:px-0">
            <nav class="mb-4 flex flex-wrap gap-2 text-sm">
                <a href="{{ route('supplier.orders.index') }}" class="rounded-full px-3 py-1 {{ $status === null ? 'bg-stone-800 text-white' : 'bg-white text-stone-600 border border-stone-200' }}">All</a>
                @foreach (\App\Domain\Orders\Enums\SupplierOrderStatus::cases() as $case)
                    <a href="{{ route('supplier.orders.index', ['status' => $case->value]) }}"
                       class="rounded-full px-3 py-1 {{ $status === $case ? 'bg-stone-800 text-white' : 'bg-white text-stone-600 border border-stone-200' }}">{{ $case->label() }}</a>
                @endforeach
            </nav>

            <div class="card overflow-x-auto">
                <table class="min-w-full divide-y divide-stone-200 text-sm">
                    <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Placed</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3 text-right">Items</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Your payout</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @forelse ($orders as $so)
                            <tr class="hover:bg-stone-50">
                                <td class="px-4 py-3 font-medium"><a href="{{ route('supplier.orders.show', $so) }}" class="text-brand-700 hover:underline">{{ $so->number }}</a></td>
                                <td class="px-4 py-3 text-stone-600">{{ $so->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3 text-stone-600">{{ $so->order->delivery_name }} · {{ $so->order->delivery_area }}</td>
                                <td class="px-4 py-3 text-right">{{ $so->items_count }}</td>
                                <td class="px-4 py-3 text-right">{{ $so->total }}</td>
                                <td class="px-4 py-3 text-right">{{ $so->payoutDue() }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="$so->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-stone-500">No paid orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
