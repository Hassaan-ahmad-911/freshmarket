<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">Store profile</h2>
    </x-slot>

    <div class="pb-12 pt-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-0">
            {{-- enctype is required for file uploads (the logo). --}}
            <form method="POST" action="{{ route('supplier.store.update') }}" enctype="multipart/form-data" class="card p-6 space-y-6">
                @csrf
                @method('PUT')

                <div class="flex items-center gap-4">
                    <x-supplier-logo :supplier="$supplier" class="h-20 w-20 text-2xl" />
                    <div class="flex-1">
                        <x-input-label for="logo" value="Logo" />
                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-stone-600">
                        <p class="mt-1 text-xs text-stone-500">JPG, PNG or WebP, up to 2 MB.</p>
                        <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                        @if ($supplier->logo_path)
                            <label class="mt-2 flex items-center gap-2 text-sm text-stone-600">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-stone-300"> Remove current logo
                            </label>
                        @endif
                    </div>
                </div>

                <div>
                    <x-input-label for="name" value="Store name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $supplier->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-stone-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ old('description', $supplier->description) }}</textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="min_order_amount" value="Minimum order ($)" />
                    <x-text-input id="min_order_amount" name="min_order_amount" type="text" inputmode="decimal" class="mt-1 block w-40"
                                  :value="old('min_order_amount', $supplier->min_order_amount->toDecimal())" placeholder="0.00" />
                    <p class="mt-1 text-xs text-stone-500">Customers must spend at least this much with your store per order. 0 = no minimum.</p>
                    <x-input-error :messages="$errors->get('min_order_amount')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="delivery_areas" value="Delivery areas" />
                    <textarea id="delivery_areas" name="delivery_areas" rows="3" placeholder="Camden, Islington, Hackney"
                              class="mt-1 block w-full border-stone-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ old('delivery_areas', $supplier->deliveryAreas->pluck('name')->join(', ')) }}</textarea>
                    <p class="mt-1 text-xs text-stone-500">Separate with commas or new lines. Customers can filter by these.</p>
                    <x-input-error :messages="$errors->get('delivery_areas')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Save profile</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
