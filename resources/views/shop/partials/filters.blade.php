{{-- Search + filter form, shared by the catalogue and store pages. Expects $categories and $action. --}}
<form method="GET" action="{{ $action }}" class="card space-y-5 p-5 lg:sticky lg:top-24">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold text-ink">Filters</h2>
        <a href="{{ $action }}" class="text-xs font-medium text-stone-500 hover:text-brand-700">Reset</a>
    </div>

    <div>
        <label for="q" class="label">Search</label>
        <div class="relative mt-1.5">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Search products" class="input pl-9">
        </div>
    </div>

    <fieldset>
        <legend class="label">Category</legend>
        <div class="mt-2 flex flex-wrap gap-2">
            <label class="cursor-pointer">
                <input type="radio" name="category" value="" class="peer sr-only" @checked(! request('category'))>
                <span class="inline-block rounded-full border border-stone-200 px-3 py-1.5 text-xs font-medium text-stone-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 hover:border-stone-300">All</span>
            </label>
            @foreach ($categories as $category)
                <label class="cursor-pointer">
                    <input type="radio" name="category" value="{{ $category->slug }}" class="peer sr-only" @checked(request('category') === $category->slug)>
                    <span class="inline-block rounded-full border border-stone-200 px-3 py-1.5 text-xs font-medium text-stone-600 transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 hover:border-stone-300">{{ $category->name }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <fieldset>
        <legend class="label">Price ($)</legend>
        <div class="mt-1.5 flex items-center gap-2">
            <input type="text" inputmode="decimal" name="min_price" value="{{ request('min_price') }}" placeholder="Min" aria-label="Minimum price" class="input">
            <span class="text-stone-400">–</span>
            <input type="text" inputmode="decimal" name="max_price" value="{{ request('max_price') }}" placeholder="Max" aria-label="Maximum price" class="input">
        </div>
        <x-input-error :messages="$errors->get('min_price')" class="mt-1" />
        <x-input-error :messages="$errors->get('max_price')" class="mt-1" />
    </fieldset>

    @unless ($hideArea ?? false)
        <div>
            <label for="area" class="label">Delivers to</label>
            <div class="relative mt-1.5">
                <x-icon name="map-pin" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <input id="area" type="text" name="area" value="{{ request('area') }}" placeholder="Your town or district" class="input pl-9">
            </div>
        </div>
    @endunless

    <div>
        <label for="sort" class="label">Sort by</label>
        <select id="sort" name="sort" class="input mt-1.5">
            @foreach (['newest' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name'] as $value => $label)
                <option value="{{ $value }}" @selected(request('sort', 'newest') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <label class="flex items-center gap-2.5 text-sm text-stone-700">
        <input type="checkbox" name="in_stock" value="1" @checked(request('in_stock')) class="h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500">
        In stock only
    </label>

    <button class="btn-primary w-full">Apply filters</button>
</form>
