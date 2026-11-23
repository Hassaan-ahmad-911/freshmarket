<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-900">Sell on FreshMarket</h1>
    <p class="mt-1 text-sm text-gray-600">Create your supplier account. An admin reviews every new store before it goes live.</p>

    <form method="POST" action="{{ route('supplier.register') }}" class="mt-6 space-y-4">
        @csrf

        <fieldset class="space-y-4">
            <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your store</legend>
            <div>
                <x-input-label for="store_name" value="Store name" />
                <x-text-input id="store_name" class="block mt-1 w-full" type="text" name="store_name" :value="old('store_name')" required autofocus />
                <x-input-error :messages="$errors->get('store_name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="description" value="Short description (optional)" />
                <textarea id="description" name="description" rows="2" class="block mt-1 w-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </fieldset>

        <fieldset class="space-y-4 pt-2">
            <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500">Your account</legend>
            <div>
                <x-input-label for="name" value="Your name" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirm password" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>
        </fieldset>

        <div class="flex items-center justify-between pt-2">
            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">Already registered?</a>
            <x-primary-button>Create store</x-primary-button>
        </div>
    </form>
</x-guest-layout>
