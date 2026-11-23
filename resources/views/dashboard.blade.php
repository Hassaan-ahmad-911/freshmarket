<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-extrabold tracking-tight text-ink">{{ __('My Account') }}</h2>
    </x-slot>

    <div class="container-page pb-12 pt-6">
        <x-empty-state icon="home" :title="'Welcome, '.Auth::user()->name.'!'" text="Your account is ready. Browse fresh food from independent local suppliers.">
            <a href="{{ route('products.index') }}" class="btn-primary rounded-full px-6">Browse products</a>
        </x-empty-state>
    </div>
</x-app-layout>
