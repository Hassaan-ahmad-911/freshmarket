<x-shop-layout title="Your basket">
    <div class="container-page py-10">
        <h1 class="text-3xl font-extrabold tracking-tight text-ink">Your basket</h1>

        @if ($errors->any())
            <div class="mt-4 flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><x-icon name="warning" /> {{ $errors->first() }}</div>
        @endif

        @if ($summary->isEmpty())
            <x-empty-state icon="basket" title="Your basket is empty." text="Discover seasonal produce, fresh bread and dairy from independent suppliers near you." class="mt-8">
                <a href="{{ route('products.index') }}" class="btn-primary rounded-full px-6">Start shopping</a>
            </x-empty-state>
        @else
            <p class="mt-2 text-sm text-stone-500">
                Items from {{ $summary->groups->count() }} {{ \Illuminate\Support\Str::plural('supplier', $summary->groups->count()) }} —
                one checkout, one payment. Each supplier prepares and delivers its own part.
            </p>

            <div class="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2" data-reveal-stagger>
                    @foreach ($summary->groups as $group)
                        <section class="card overflow-hidden" data-reveal>
                            <header class="flex items-center justify-between gap-3 border-b border-stone-100 bg-stone-50/60 px-5 py-4">
                                <a href="{{ route('stores.show', $group->supplier) }}" class="flex items-center gap-3 font-semibold text-ink hover:text-brand-700">
                                    <x-supplier-logo :supplier="$group->supplier" class="h-10 w-10 text-xs shadow-soft" />
                                    <span>
                                        {{ $group->supplier->name }}
                                        <span class="block text-xs font-normal text-stone-500">{{ $group->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $group->items->sum('quantity')) }}</span>
                                    </span>
                                </a>
                                <span class="text-sm font-semibold text-stone-700">{{ $group->subtotal }}</span>
                            </header>

                            <ul class="divide-y divide-stone-100">
                                @foreach ($group->items as $item)
                                    <li class="flex items-center gap-4 px-5 py-4">
                                        <a href="{{ route('products.show', $item->product) }}" class="group shrink-0">
                                            <x-product-image :product="$item->product" class="h-20 w-20 rounded-xl" />
                                        </a>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('products.show', $item->product) }}" class="font-semibold text-ink hover:text-brand-700">{{ $item->product->name }}</a>
                                            <p class="mt-0.5 text-xs text-stone-500">{{ $item->product->price }} @if($item->product->unit) / {{ $item->product->unit }} @endif</p>
                                            <form method="POST" action="{{ route('cart.update', $item) }}" class="mt-2 inline-flex items-center gap-2">
                                                @csrf @method('PATCH')
                                                <label for="qty-{{ $item->id }}" class="text-xs text-stone-500">Qty</label>
                                                <input id="qty-{{ $item->id }}" type="number" name="quantity" min="0" max="{{ $item->product->stock }}" value="{{ $item->quantity }}"
                                                       class="w-16 rounded-lg border-stone-300 py-1 text-sm focus:border-brand-500 focus:ring-brand-500" onchange="this.form.submit()">
                                            </form>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            <span class="font-semibold text-ink">{{ $item->lineTotal() }}</span>
                                            <form method="POST" action="{{ route('cart.destroy', $item) }}">
                                                @csrf @method('DELETE')
                                                <button class="rounded-lg p-1.5 text-stone-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Remove {{ $item->product->name }}"><x-icon name="trash" class="h-4 w-4" /></button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>

                            <footer class="space-y-1 border-t border-stone-100 px-5 py-3 text-sm">
                                <div class="flex justify-between text-stone-600">
                                    <span class="flex items-center gap-1.5"><x-icon name="truck" class="h-4 w-4" /> Delivery from {{ $group->supplier->name }}</span>
                                    <span>{{ $group->deliveryFee->isZero() ? 'Free' : $group->deliveryFee }}</span>
                                </div>
                                @unless ($group->meetsMinimum())
                                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-amber-800">Add {{ $group->shortfall() }} more to reach this store's {{ $group->supplier->min_order_amount }} minimum.</p>
                                @endunless
                            </footer>
                        </section>
                    @endforeach
                </div>

                <aside class="card p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-semibold text-ink">Order summary</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between text-stone-600"><dt>Items</dt><dd>{{ $summary->subtotal() }}</dd></div>
                        <div class="flex justify-between text-stone-600"><dt>Delivery ({{ $summary->groups->count() }} {{ \Illuminate\Support\Str::plural('supplier', $summary->groups->count()) }})</dt><dd>{{ $summary->deliveryTotal() }}</dd></div>
                        <div class="flex justify-between border-t border-stone-100 pt-3 text-base font-bold text-ink"><dt>Total</dt><dd>{{ $summary->total() }}</dd></div>
                    </dl>
                    @if ($summary->canCheckout())
                        <a href="{{ route('checkout.create') }}" class="btn-primary mt-6 w-full rounded-full py-3.5 text-base">Checkout <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    @else
                        <p class="mt-6 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Every store's minimum order must be met before checkout.</p>
                    @endif
                    <p class="mt-4 flex items-center justify-center gap-1.5 text-xs text-stone-500"><x-icon name="shield-check" class="h-4 w-4 text-brand-600" /> Secure payment with Stripe</p>
                </aside>
            </div>
        @endif
    </div>
</x-shop-layout>
