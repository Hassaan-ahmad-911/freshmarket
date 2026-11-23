<x-shop-layout title="Checkout">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-2xl font-bold">Checkout</h1>

        @error('cart')
            <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                @foreach ($errors->get('cart') as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @enderror

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf
            <section class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                <h2 class="font-semibold">Delivery details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="delivery_name" value="Full name" />
                        <x-text-input id="delivery_name" name="delivery_name" class="mt-1 block w-full" :value="old('delivery_name', $user->name)" required />
                        <x-input-error :messages="$errors->get('delivery_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="delivery_phone" value="Phone" />
                        <x-text-input id="delivery_phone" name="delivery_phone" class="mt-1 block w-full" :value="old('delivery_phone')" required />
                        <x-input-error :messages="$errors->get('delivery_phone')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="delivery_address" value="Street address" />
                        <x-text-input id="delivery_address" name="delivery_address" class="mt-1 block w-full" :value="old('delivery_address')" required />
                        <x-input-error :messages="$errors->get('delivery_address')" class="mt-1" />
                    </div>
                    <div>
                        @php($areas = $summary->groups->flatMap(fn ($g) => $g->supplier->deliveryAreas->pluck('name'))->unique()->sort()->values())
                        <x-input-label for="delivery_area" value="Area" />
                        <x-text-input id="delivery_area" name="delivery_area" list="areas" class="mt-1 block w-full" :value="old('delivery_area')" required />
                        <datalist id="areas">@foreach ($areas as $area)<option value="{{ $area }}">@endforeach</datalist>
                        <x-input-error :messages="$errors->get('delivery_area')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="delivery_notes" value="Notes for the suppliers (optional)" />
                        <textarea id="delivery_notes" name="delivery_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('delivery_notes') }}</textarea>
                    </div>
                </div>
            </section>

            <aside class="bg-white rounded-xl border border-gray-200 p-5 h-fit text-sm space-y-3">
                <h2 class="font-semibold text-base">Your order</h2>
                @foreach ($summary->groups as $group)
                    <div class="border-b border-gray-100 pb-3">
                        <p class="font-medium">{{ $group->supplier->name }}</p>
                        @foreach ($group->items as $item)
                            <div class="flex justify-between text-gray-600"><span>{{ $item->quantity }} × {{ $item->product->name }}</span><span>{{ $item->lineTotal() }}</span></div>
                        @endforeach
                        <div class="flex justify-between text-gray-500"><span>Delivery</span><span>{{ $group->deliveryFee->isZero() ? 'Free' : $group->deliveryFee }}</span></div>
                    </div>
                @endforeach
                <div class="flex justify-between text-base font-bold"><span>Total</span><span>{{ $summary->total() }}</span></div>
                <button class="w-full rounded-lg bg-emerald-600 px-4 py-3 font-semibold text-white hover:bg-emerald-700">Continue to payment</button>
                <p class="text-xs text-gray-500">You pay once. FreshMarket passes each supplier their share.</p>
            </aside>
        </form>
    </div>
</x-shop-layout>
