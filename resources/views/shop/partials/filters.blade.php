{{-- Search + filter form, shared by the catalogue and store pages. Expects $categories and $action. --}}
<form method="GET" action="{{ $action }}" class="space-y-5 rounded-xl bg-white border border-gray-200 p-4">
    <div>
        <label for="q" class="block text-sm font-medium text-gray-700">Search</label>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="e.g. apples"
               class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
    </div>

    <div>
        <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
        <select id="category" name="category" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <option value="">All categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->icon }} {{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <fieldset>
        <legend class="block text-sm font-medium text-gray-700">Price ($)</legend>
        <div class="mt-1 flex items-center gap-2">
            <input type="text" inputmode="decimal" name="min_price" value="{{ request('min_price') }}" placeholder="Min" aria-label="Minimum price"
                   class="w-full rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            <span class="text-gray-400">–</span>
            <input type="text" inputmode="decimal" name="max_price" value="{{ request('max_price') }}" placeholder="Max" aria-label="Maximum price"
                   class="w-full rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <x-input-error :messages="$errors->get('min_price')" class="mt-1" />
        <x-input-error :messages="$errors->get('max_price')" class="mt-1" />
    </fieldset>

    @unless ($hideArea ?? false)
        <div>
            <label for="area" class="block text-sm font-medium text-gray-700">Delivers to</label>
            <input id="area" type="text" name="area" value="{{ request('area') }}" placeholder="Your town or district"
                   class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
        </div>
    @endunless

    <div>
        <label for="sort" class="block text-sm font-medium text-gray-700">Sort by</label>
        <select id="sort" name="sort" class="mt-1 w-full rounded-md border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
            @foreach (['newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name'] as $value => $label)
                <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="in_stock" value="1" @checked(request('in_stock')) class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
        In stock only
    </label>

    <div class="flex gap-2">
        <button class="flex-1 rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">Apply</button>
        <a href="{{ $action }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">Reset</a>
    </div>
</form>
