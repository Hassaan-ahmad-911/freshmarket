<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Products</h2>
            <a href="{{ route('supplier.products.create') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">+ New product</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" class="mb-4 flex gap-2 max-w-md">
                <x-text-input type="search" name="q" :value="request('q')" placeholder="Search your products" class="flex-1 text-sm" />
                <x-secondary-button type="submit">Search</x-secondary-button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($products->isEmpty())
                    <div class="p-12 text-center text-gray-500">
                        <p>No products yet.</p>
                        <a href="{{ route('supplier.products.create') }}" class="mt-2 inline-block text-emerald-700 hover:underline">Add your first product</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3 text-right">Price</th>
                                    <th class="px-4 py-3 text-right">Stock</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <x-product-image :product="$product" class="h-10 w-10 rounded-md shrink-0 text-base" />
                                                <span class="font-medium">{{ $product->name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $product->category->name }}</td>
                                        <td class="px-4 py-3 text-right">{{ $product->price }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <span @class(['font-medium', 'text-red-600' => $product->stock === 0, 'text-amber-600' => $product->stock > 0 && $product->isLowStock()])>
                                                {{ $product->stock }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('supplier.products.toggle', $product) }}">
                                                @csrf @method('PATCH')
                                                <button class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}"
                                                        title="Click to {{ $product->is_active ? 'hide' : 'show' }}">
                                                    {{ $product->is_active ? 'Active' : 'Hidden' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('supplier.products.edit', $product) }}" class="text-emerald-700 hover:underline">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100">{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
