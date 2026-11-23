{{-- Shared fields for create + edit. Expects $product and $categories. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category_id" value="Category" />
        <select id="category_id" name="category_id" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">Choose…</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->icon }} {{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <x-input-label for="price" value="Price ($)" />
            <x-text-input id="price" name="price" type="text" inputmode="decimal" class="mt-1 block w-full" placeholder="4.99"
                          :value="old('price', $product->price?->toDecimal())" required />
            <x-input-error :messages="$errors->get('price')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="unit" value="Per (unit)" />
            <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" placeholder="kg, each, 500 g" :value="old('unit', $product->unit)" />
            <x-input-error :messages="$errors->get('unit')" class="mt-2" />
        </div>
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $product->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="stock" value="Stock" />
        <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 block w-full" :value="old('stock', $product->stock)" required />
        <x-input-error :messages="$errors->get('stock')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="low_stock_threshold" value="Low-stock alert at" />
        <x-text-input id="low_stock_threshold" name="low_stock_threshold" type="number" min="0" class="mt-1 block w-full" :value="old('low_stock_threshold', $product->low_stock_threshold)" required />
        <p class="mt-1 text-xs text-gray-500">You get an email when stock falls to this number.</p>
        <x-input-error :messages="$errors->get('low_stock_threshold')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="images" value="Add images" />
        <input id="images" name="images[]" type="file" multiple accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-gray-600">
        <p class="mt-1 text-xs text-gray-500">Up to {{ \App\Domain\Catalog\Models\Product::MAX_IMAGES }} images, 2 MB each. The first image is the main photo.</p>
        <x-input-error :messages="$errors->get('images')" class="mt-2" />
        <x-input-error :messages="$errors->get('images.*')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        {{-- Hidden "0" first, so an unticked checkbox still sends a value. --}}
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
            Visible to customers
        </label>
    </div>
</div>
