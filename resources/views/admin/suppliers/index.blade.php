<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Suppliers</h2>
        <p class="text-sm text-gray-500">Temporary approval screen — replaced by the Filament admin panel in Phase 6.</p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Store</th>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Registered</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $supplier->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $supplier->owner->name }}<br><span class="text-xs">{{ $supplier->owner->email }}</span></td>
                                <td class="px-4 py-3"><x-supplier-status-badge :status="$supplier->status" /></td>
                                <td class="px-4 py-3 text-gray-600">{{ $supplier->created_at->toFormattedDateString() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        @unless ($supplier->isApproved())
                                            <form method="POST" action="{{ route('admin.suppliers.approve', $supplier) }}">
                                                @csrf
                                                <button class="rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">Approve</button>
                                            </form>
                                        @endunless
                                        @unless ($supplier->isSuspended())
                                            <form method="POST" action="{{ route('admin.suppliers.suspend', $supplier) }}">
                                                @csrf
                                                <button class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">
                                                    {{ $supplier->isPending() ? 'Reject' : 'Suspend' }}
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No suppliers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
