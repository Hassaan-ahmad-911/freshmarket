<x-shop-layout title="Checkout">
    <div class="container-page py-10">
        <h1 class="text-3xl font-extrabold tracking-tight text-ink">Checkout</h1>

        @error('cart')
            <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->get('cart') as $message)<p class="flex items-start gap-2"><x-icon name="warning" class="h-4 w-4 mt-0.5" /> {{ $message }}</p>@endforeach
            </div>
        @enderror

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid grid-cols-1 items-start gap-8 lg:grid-cols-3">
            @csrf
            <section class="card space-y-5 p-6 lg:col-span-2 animate-fade-up">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="map-pin" /></span>
                    <h2 class="text-lg font-semibold text-ink">Delivery details</h2>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="delivery_name" class="label">Full name</label>
                        <input id="delivery_name" name="delivery_name" class="input mt-1.5" value="{{ old('delivery_name', $user->name) }}" required>
                        <x-input-error :messages="$errors->get('delivery_name')" class="mt-1" />
                    </div>
                    <div>
                        <label for="delivery_phone" class="label">Phone</label>
                        <input id="delivery_phone" name="delivery_phone" class="input mt-1.5" value="{{ old('delivery_phone') }}" required>
                        <x-input-error :messages="$errors->get('delivery_phone')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="delivery_address" class="label">Street address</label>
                        <input id="delivery_address" name="delivery_address" class="input mt-1.5" value="{{ old('delivery_address') }}" required>
                        <x-input-error :messages="$errors->get('delivery_address')" class="mt-1" />
                    </div>
                    <div>
                        @php($areas = $summary->groups->flatMap(fn ($g) => $g->supplier->deliveryAreas->pluck('name'))->unique()->sort()->values())
                        <label for="delivery_area" class="label">Area</label>
                        <input id="delivery_area" name="delivery_area" list="areas" class="input mt-1.5" value="{{ old('delivery_area') }}" required>
                        <datalist id="areas">@foreach ($areas as $area)<option value="{{ $area }}">@endforeach</datalist>
                        <x-input-error :messages="$errors->get('delivery_area')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="delivery_notes" class="label">Notes for the suppliers (optional)</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="2" class="input mt-1.5">{{ old('delivery_notes') }}</textarea>
                    </div>
                </div>
            </section>

            <aside class="card space-y-4 p-6 text-sm lg:sticky lg:top-24 animate-fade-up [animation-delay:100ms]">
                <h2 class="text-lg font-semibold text-ink">Your order</h2>
                @foreach ($summary->groups as $group)
                    <div class="border-b border-stone-100 pb-4">
                        <p class="flex items-center gap-2 font-semibold text-ink">
                            <x-supplier-logo :supplier="$group->supplier" class="h-6 w-6 text-[9px] ring-1" /> {{ $group->supplier->name }}
                        </p>
                        <div class="mt-2 space-y-1">
                            @foreach ($group->items as $item)
                                <div class="flex justify-between text-stone-600"><span>{{ $item->quantity }} × {{ $item->product->name }}</span><span>{{ $item->lineTotal() }}</span></div>
                            @endforeach
                            <div class="flex justify-between text-stone-500"><span>Delivery</span><span>{{ $group->deliveryFee->isZero() ? 'Free' : $group->deliveryFee }}</span></div>
                        </div>
                    </div>
                @endforeach
                <div class="flex justify-between text-base font-bold text-ink"><span>Total</span><span>{{ $summary->total() }}</span></div>
                <button class="btn-primary w-full rounded-full py-3.5 text-base"><x-icon name="card" /> Continue to payment</button>
                <p class="flex items-start gap-1.5 text-xs text-stone-500"><x-icon name="shield-check" class="h-4 w-4 shrink-0 text-brand-600" /> You pay once. FreshMarket passes each supplier their share.</p>
            </aside>
        </form>
    </div>
</x-shop-layout>
