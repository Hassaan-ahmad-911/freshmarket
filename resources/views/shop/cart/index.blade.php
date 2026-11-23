<x-shop-layout title="Your basket">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold">Your basket</h1>

        @if ($errors->any())
            <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        @if ($summary->isEmpty())
            <div class="mt-6 rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-4xl" aria-hidden="true">🧺</p>
                <p class="mt-2 font-medium">Your basket is empty.</p>
                <a href="{{ route('products.index') }}" class="mt-2 inline-block text-emerald-700 hover:underline">Start shopping</a>
            </div>
        @else
            <p class="mt-1 text-sm text-gray-500">
                Items from {{ $summary->groups->count() }} {{ \Illuminate\Support\Str::plural('supplier', $summary->groups->count()) }} —
                one checkout, one payment. Each supplier prepares and delivers its own part.
            </p>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-4">
                    @foreach ($summary->groups as $group)
                        <section class="bg-white rounded-xl border border-gray-200">
                            <header class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                                <a href="{{ route('stores.show', $group->supplier) }}" class="flex items-center gap-2 font-semibold hover:text-emerald-700">
                                    <x-supplier-logo :supplier="$group->supplier" class="h-7 w-7 text-xs" />
                                    {{ $group->supplier->name }}
                                </a>
                                <span class="text-sm text-gray-500">{{ $group->subtotal }}</span>
                            </header>

                            <ul class="divide-y divide-gray-100">
                                @foreach ($group->items as $item)
                                    <li class="flex items-center gap-4 px-4 py-3">
                                        <x-product-image :product="$item->product" class="h-14 w-14 rounded-lg shrink-0 text-xl" />
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('products.show', $item->product) }}" class="font-medium hover:text-emerald-700">{{ $item->product->name }}</a>
                                            <p class="text-xs text-gray-500">{{ $item->product->price }} @if($item->product->unit) / {{ $item->product->unit }} @endif</p>
                                        </div>
                                        <form method="POST" action="{{ route('cart.update', $item) }}" class="flex items-center gap-1">
                                            @csrf @method('PATCH')
                                            <label for="qty-{{ $item->id }}" class="sr-only">Quantity</label>
                                            <input id="qty-{{ $item->id }}" type="number" name="quantity" min="0" max="{{ $item->product->stock }}" value="{{ $item->quantity }}"
                                                   class="w-16 rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                                        </form>
                                        <span class="w-20 text-right font-medium">{{ $item->lineTotal() }}</span>
                                        <form method="POST" action="{{ route('cart.destroy', $item) }}">
                                            @csrf @method('DELETE')
                                            <button class="text-gray-400 hover:text-red-600" aria-label="Remove {{ $item->product->name }}">&times;</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>

                            <footer class="px-4 py-3 border-t border-gray-100 text-sm space-y-1">
                                <div class="flex justify-between text-gray-600">
                                    <span>Delivery from {{ $group->supplier->name }}</span>
                                    <span>{{ $group->deliveryFee->isZero() ? 'Free' : $group->deliveryFee }}</span>
                                </div>
                                @unless ($group->meetsMinimum())
                                    <p class="text-amber-700">Add {{ $group->shortfall() }} more to reach this store's {{ $group->supplier->min_order_amount }} minimum.</p>
                                @endunless
                            </footer>
                        </section>
                    @endforeach
                </div>

                <aside class="bg-white rounded-xl border border-gray-200 p-5 h-fit space-y-2 text-sm">
                    <h2 class="font-semibold text-base">Order summary</h2>
                    <div class="flex justify-between"><span>Items</span><span>{{ $summary->subtotal() }}</span></div>
                    <div class="flex justify-between"><span>Delivery ({{ $summary->groups->count() }} {{ \Illuminate\Support\Str::plural('supplier', $summary->groups->count()) }})</span><span>{{ $summary->deliveryTotal() }}</span></div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold"><span>Total</span><span>{{ $summary->total() }}</span></div>
                    @if ($summary->canCheckout())
                        <a href="{{ route('checkout.create') }}" class="mt-3 block rounded-lg bg-emerald-600 px-4 py-3 text-center font-semibold text-white hover:bg-emerald-700">Checkout</a>
                    @else
                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-amber-800">Every store's minimum order must be met before checkout.</p>
                    @endif
                </aside>
            </div>
        @endif
    </div>
</x-shop-layout>
